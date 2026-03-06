<?php

// ─────────────────────────────────────────────────────────────────────────────
//  FILE: app/Services/EsimService.php
// ─────────────────────────────────────────────────────────────────────────────

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class EsimService
{
    // ── SendGrid Template IDs ─────────────────────────────────────────────────
    // config/services.php ya seedha .env mein rakh sakte ho
    const TEMPLATE_USER_SUCCESS = 'd-8d0b0c5bb0f741a892541c880c0b1b04';
    const TEMPLATE_ADMIN_ALERT = 'd-96390228b0804796987dc58ebb5284b8';

    // ── BST API credentials (from .env) ───────────────────────────────────────
    protected string $apiUser;
    protected string $apiPassword;
    protected string $apiUrl;
    protected string $distributorId;
    protected string $adminEmail;

    // ── Per-activation state ──────────────────────────────────────────────────
    protected int $currentOrderId = 0;
    protected string $currentMasterUid = '';
    protected array $stepLog = [];  // email summary ke liye

    public function __construct()
    {
        $this->apiUser = config('services.esim.api_user') ?: throw new \RuntimeException('ESIM_API_USER missing in .env');
        $this->apiPassword = config('services.esim.api_pass') ?: throw new \RuntimeException('ESIM_API_PASS missing in .env');
        $this->apiUrl = config('services.esim.api_url') ?: throw new \RuntimeException('ESIM_API_URL missing in .env');
        $this->distributorId = config('services.esim.distributor_id', '14597879');
        $this->adminEmail = config('services.esim.admin_email') ?: throw new \RuntimeException('ESIM_ADMIN_EMAIL missing in .env');
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  PUBLIC — New eSIM Activation
    //  Called by: ProcessEsimActivation Job
    // ═════════════════════════════════════════════════════════════════════════

    public function activateNewEsim(int $orderId, string $masterUid): array
    {
        // State reset (important for job retries — same instance nahi milta but clean rakho)
        $this->currentOrderId = $orderId;
        $this->currentMasterUid = $masterUid;
        $this->stepLog = [];

        $this->log('info', 'ACTIVATION START', ['order_id' => $orderId, 'master_uid' => $masterUid]);

        // ── Idempotency — already active toh kuch mat karo ───────────────────
        $existingOrder = DB::table('orders')->where('id', $orderId)->first();
        if ($existingOrder && $existingOrder->status === 'ACTIVE') {
            $this->log('info', 'Already ACTIVE — skipping', ['order_id' => $orderId]);
            return ['success' => true, 'skipped' => true];
        }

        // ── Load required data ────────────────────────────────────────────────
        $order = DB::table('orders')->where('id', $orderId)->first();
        if (!$order)
            throw new \RuntimeException("Order not found: {$orderId}");

        $user = DB::table('users')->where('id', $order->userId)->first();
        if (!$user)
            throw new \RuntimeException("User not found: {$order->userId}");

        $plan = DB::table('plans')->where('id', $order->plan_id)->first();
        if (!$plan)
            throw new \RuntimeException("Plan not found: {$order->plan_id}");

        // ── ICCID reserve — DB transaction se race condition safe ─────────────
        // $iccidRow = DB::transaction(function () {
        //     $row = DB::table('ICCID')
        //         ->where('status', 'PROVISIONED')
        //         ->where('id', '>', 5049)
        //         ->lockForUpdate()
        //         ->first();

        //     if (!$row)
        //         throw new \RuntimeException('No ICCID available in inventory');

        //     DB::table('ICCID')->where('id', $row->id)->update(['status' => 'IN PROGRESS']);
        //     return $row;
        // });
        //*********testing — ICCID 6632 status kabhi mat badlo ──────────────
        $iccidRow = DB::table('ICCID')->where('id', 6632)->first();
        if (!$iccidRow) {
            throw new \RuntimeException('Testing ICCID 6632 not found in DB');
        }

        // ── Variables ─────────────────────────────────────────────────────────
        $email = $user->email;
        $fname = trim($user->fname ?? '');
        $lname = trim($user->lname ?? '');

        // ✅ FIX — BST rejects empty first-name / surname / name fields
        // Derive sensible fallbacks from email if DB columns are blank
        if (empty($fname) && empty($lname)) {
            // e.g. "john.doe@gmail.com" → fname="john.doe", lname="Customer"
            $localPart = strstr($email, '@', true) ?: $email;
            $fname = $localPart;
            $lname = 'Customer';
            $this->log('warning', 'fname+lname both empty — derived from email', [
                'email' => $email,
                'fname' => $fname,
                'lname' => $lname,
            ]);
        } elseif (empty($fname)) {
            $fname = $lname;  // at least one is non-empty
        } elseif (empty($lname)) {
            $lname = $fname;
        }

        $name = trim("{$fname} {$lname}");
        // Final safety net — should never be empty now
        if (empty($name)) {
            $name = $email;
        }

        $phone = $this->formatPhone($user->mobile ?? '', $user->isdcode ?? '');
        // ✅ FIX — BST requires ISO 3166-1 alpha-3 country codes (3 letters)
        // Users table mein alpha-2 (IN, US, GB) ho sakta hai — convert karo
        $country = $this->toAlpha3($user->country ?: 'ISR');
        $ICCID = $iccidRow->ICCID;
        $msisdn = $iccidRow->Camel_MSISDN;
        $matchingCode = $iccidRow->Matching_Code ?: $iccidRow->LPA_Value;
        $planMoniker = $plan->Moniker;
        $planName = $plan->plan_name;
        $zoneId = (string) $plan->zone_id;
        $mins = (int) ($order->Mins ?? 0);
        $bonusData = (int) ($order->bonus_data ?? 0);
        $promoCode = $order->promocode ?? '';
        $autorenew = $order->autorenew ?? 0;
        $zn = $this->getZoneCode($zoneId);
        $displayPlan = "{$zn}-{$planName} - {$planMoniker}";
        $arNotes = 'Auto Topup: ' . ($autorenew ? 'Yes' : 'No');
        $bonusMoniker = $this->getBonusMoniker($bonusData);
        $emailDomain = strtolower(substr(strrchr($email, '@'), 1));
        $custStatus = ($emailDomain === 'gufum.com') ? 'suspended' : 'autoactivate';

        // Mark order IN PROGRESS + assign ICCID
        DB::table('orders')->where('id', $orderId)->update([
            'status' => 'IN PROGRESS',
            'inventoryId' => $iccidRow->id,
            'msisdn' => $msisdn,
        ]);

        $this->log('info', 'ICCID Reserved', ['ICCID' => $ICCID, 'msisdn' => $msisdn]);

        // ── BST API Steps ─────────────────────────────────────────────────────
        $stepCount = 0;
        $customerId = '';
        $subscriberId = '';

        try {

            $this->log('info', 'inside try ready to start apis');

            // ╔══════════════════════════════════════════════════════════╗
            // ║  STEP 1 — Create Customer & Subscriber                   ║
            // ╚══════════════════════════════════════════════════════════╝
            $req = $this->buildCreateCustomerXml(compact(
                'name',
                'msisdn',
                'ICCID',
                'custStatus',
                'email',
                'phone',
                'displayPlan',
                'matchingCode',
                'arNotes',
                'orderId',
                'country',
                'fname',
                'lname'
            ));

            // ✅ DEBUG — log the exact XML being sent so we can see what BST receives
            $this->log('info', 'Step 1 REQUEST XML', [
                'name' => $name,
                'fname' => $fname,
                'lname' => $lname,
                'xml_preview' => substr($req, 0, 600),
            ]);
            $res = $this->callBstApi($req);
            $this->recordStep($order, $stepCount, 'create-customer-and-subscriber', $req, $res);

            // ✅ FIX 1 — hasError() now catches BST <*-error> root tags,
            //            so this will correctly throw before we try to parse IDs
            if ($this->hasError($res)) {
                throw new \RuntimeException('Step 1 failed: ' . $this->extractError($res));
            }

            Log::info('BST RAW RESPONSE', ['response' => $res]);
            $stepCount = 1;

            // ✅ FIX 4 — Robust ID extraction with debug logging
            $xml = $this->parseXml($res, 'Step 1');

            // Log raw XML structure for debugging unexpected BST response shapes
            $this->log('info', 'Step 1 XML structure', [
                'root' => $xml->getName(),
                'raw' => substr($res, 0, 800),
            ]);

            // Try attribute first (id="123"), then child element (<id>123</id>)
            $customerId = (string) ($xml->customer['id'] ?? $xml->customer->id ?? '');
            $subscriberId = (string) ($xml->subscriber['id'] ?? $xml->subscriber->id ?? '');

            // Fallback: alternate node names BST might use
            if (empty($customerId)) {
                $customerId = (string) ($xml->customerid ?? $xml->{'customer-id'} ?? '');
            }
            if (empty($subscriberId)) {
                $subscriberId = (string) ($xml->subscriberid ?? $xml->{'subscriber-id'} ?? '');
            }

            if (empty($customerId) || empty($subscriberId)) {
                throw new \RuntimeException(
                    'Step 1: customerId / subscriberId empty in BST response. Root: '
                    . $xml->getName() . ' | Raw: ' . substr($res, 0, 400)
                );
            }
            $this->log('info', 'Step 1 OK — Customer created', compact('customerId', 'subscriberId'));

            // ╔══════════════════════════════════════════════════════════╗
            // ║  STEP 2 — Create SIM                                    ║
            // ╚══════════════════════════════════════════════════════════╝
            $req = $this->buildCreateSimXml($iccidRow);
            $res = $this->callBstApi($req);
            $this->recordStep($order, $stepCount, 'create-sim', $req, $res);
            if ($this->hasError($res)) {
                // ✅ SIMAlreadyExists = SIM pehle se BST mein hai — skip karo, fatal nahi
                if (stripos($res, 'SIMAlreadyExists') !== false) {
                    $this->log('info', 'Step 2 SKIP — SIM already exists in BST (reuse OK)', ['ICCID' => $ICCID]);
                } else {
                    throw new \RuntimeException('Step 2 failed: ' . $this->extractError($res));
                }
            }
            $stepCount = 2;
            $this->log('info', 'Step 2 OK — SIM created/exists', ['ICCID' => $ICCID]);

            // ╔══════════════════════════════════════════════════════════╗
            // ║  STEP 3 — Create Directory Number                       ║
            // ╚══════════════════════════════════════════════════════════╝
            $req = $this->buildCreateDirXml($msisdn);
            $res = $this->callBstApi($req);
            $this->recordStep($order, $stepCount, 'create-directory-number', $req, $res);
            if ($this->hasError($res)) {
                if (stripos($res, 'AlreadyExists') !== false) {
                    $this->log('info', 'Step 3 SKIP — Directory number already exists (reuse OK)', ['msisdn' => $msisdn]);
                } else {
                    throw new \RuntimeException('Step 3 failed: ' . $this->extractError($res));
                }
            }
            $stepCount = 3;
            $this->log('info', 'Step 3 OK — Directory number created/exists', ['msisdn' => $msisdn]);

            // ╔══════════════════════════════════════════════════════════╗
            // ║  STEP 4 — Add Subscriber Directory Number               ║
            // ╚══════════════════════════════════════════════════════════╝
            $req = $this->buildAddDirXml($subscriberId, $msisdn);
            $res = $this->callBstApi($req);
            $this->recordStep($order, $stepCount, 'add-subscriber-directory-number', $req, $res);
            if ($this->hasError($res)) {
                if (stripos($res, 'AlreadyExists') !== false) {
                    $this->log('info', 'Step 4 SKIP — Subscriber directory number already linked (reuse OK)');
                } else {
                    throw new \RuntimeException('Step 4 failed: ' . $this->extractError($res));
                }
            }
            $stepCount = 4;
            $this->log('info', 'Step 4 OK — Subscriber directory linked/exists');

            // ╔══════════════════════════════════════════════════════════╗
            // ║  STEP 5 — Set Subscriber CLI                            ║
            // ╚══════════════════════════════════════════════════════════╝
            $req = $this->buildSetCliXml($subscriberId, $msisdn);
            $res = $this->callBstApi($req);
            $this->recordStep($order, $stepCount, 'set-subscriber-cli', $req, $res);
            if ($this->hasError($res))
                throw new \RuntimeException('Step 5 failed: ' . $this->extractError($res));
            $stepCount = 5;
            $this->log('info', 'Step 5 OK — CLI set');

            // ╔══════════════════════════════════════════════════════════╗
            // ║  STEP 6 — Add Subscriber SIM                            ║
            // ╚══════════════════════════════════════════════════════════╝
            $req = $this->buildAddSimXml($subscriberId, $ICCID);
            $res = $this->callBstApi($req);
            $this->recordStep($order, $stepCount, 'add-subscriber-sim', $req, $res);
            if ($this->hasError($res)) {
                if (stripos($res, 'AlreadyExists') !== false) {
                    $this->log('info', 'Step 6 SKIP — SIM already linked to subscriber (reuse OK)', ['ICCID' => $ICCID]);
                } else {
                    throw new \RuntimeException('Step 6 failed: ' . $this->extractError($res));
                }
            }
            $stepCount = 6;
            $this->log('info', 'Step 6 OK — SIM linked/exists on subscriber');

            // ╔══════════════════════════════════════════════════════════╗
            // ║  STEP 7 — Set Subscriber Password                       ║
            // ╚══════════════════════════════════════════════════════════╝
            $req = $this->buildSetPasswordXml($subscriberId, $msisdn);
            $res = $this->callBstApi($req);
            $this->recordStep($order, $stepCount, 'set-subscriber-password', $req, $res);
            if ($this->hasError($res))
                throw new \RuntimeException('Step 7 failed: ' . $this->extractError($res));
            $stepCount = 7;
            $this->log('info', 'Step 7 OK — Password set');

            // ╔══════════════════════════════════════════════════════════╗
            // ║  STEP 8 — Apply Bonus Promo  [NON-FATAL]                ║
            // ╚══════════════════════════════════════════════════════════╝
            if ($bonusData >= 1 && $promoCode !== 'avrupa-ruyasi' && $bonusMoniker) {
                $req = $this->buildApplyPromoXml($subscriberId, $bonusMoniker);
                $res = $this->callBstApi($req);
                $this->recordStep($order, $stepCount, "apply-promotion (bonus:{$bonusMoniker})", $req, $res);

                if ($this->hasError($res)) {
                    $this->log('warning', 'Step 8 WARN — Bonus promo failed (non-fatal)', ['moniker' => $bonusMoniker]);
                    $this->sendAdminAlert(
                        "eSIM Bonus Promo Failed — Order #{$orderId}",
                        "Step 8: apply-promotion (bonus:{$bonusMoniker})",
                        $req,
                        $res,
                        'Non-fatal — activation continues without bonus'
                    );
                } else {
                    $this->log('info', 'Step 8 OK — Bonus promo applied', ['moniker' => $bonusMoniker]);
                    sleep(10); // BST API requires delay after bonus
                }
            }

            // ╔══════════════════════════════════════════════════════════╗
            // ║  STEP 9 — Apply Main Data Plan  [CRITICAL]              ║
            // ╚══════════════════════════════════════════════════════════╝
            $stepCount = 8;
            $req = $this->buildApplyPromoXml($subscriberId, $planMoniker, date('Y-m-d') . ' 00:00:00Z');
            $res = $this->callBstApi($req);
            $this->recordStep($order, $stepCount, "apply-promotion (main:{$planMoniker})", $req, $res);
            if ($this->hasError($res))
                throw new \RuntimeException('Step 9 failed: ' . $this->extractError($res));
            $stepCount = 9;
            $this->log('info', 'Step 9 OK — Main data plan applied', ['moniker' => $planMoniker]);

            // ╔══════════════════════════════════════════════════════════╗
            // ║  STEP 10 — Apply Talk Time  [NON-FATAL, only if mins=100]║
            // ╚══════════════════════════════════════════════════════════╝
            if ($mins === 100) {
                $req = $this->buildApplyPromoXml($subscriberId, 'z1_100m', date('Y-m-d H:i:s') . 'Z');
                $res = $this->callBstApi($req);
                $this->recordStep($order, $stepCount, 'apply-promotion (talktime:z1_100m)', $req, $res);

                if ($this->hasError($res)) {
                    $this->log('warning', 'Step 10 WARN — Talk time failed (non-fatal)');
                    $this->sendAdminAlert(
                        "eSIM Talk Time Failed — Order #{$orderId}",
                        'Step 10: apply-promotion (talktime)',
                        $req,
                        $res,
                        'Non-fatal — data plan is active but no talk time'
                    );
                } else {
                    $stepCount = 10;
                    $this->log('info', 'Step 10 OK — Talk time applied');

                    // ╔══════════════════════════════════════════════════════════╗
                    // ║  STEP 11 — Apply SMS  [NON-FATAL]                       ║
                    // ╚══════════════════════════════════════════════════════════╝
                    $req = $this->buildApplyPromoXml($subscriberId, 'z1_50sms', date('Y-m-d') . ' 00:00:00Z');
                    $res = $this->callBstApi($req);
                    $this->recordStep($order, $stepCount, 'apply-promotion (sms:z1_50sms)', $req, $res);

                    if ($this->hasError($res)) {
                        $this->log('warning', 'Step 11 WARN — SMS promo failed (non-fatal)');
                        $this->sendAdminAlert(
                            "eSIM SMS Promo Failed — Order #{$orderId}",
                            'Step 11: apply-promotion (sms)',
                            $req,
                            $res,
                            'Non-fatal — data + talktime active'
                        );
                    } else {
                        $stepCount = 11;
                        $this->log('info', 'Step 11 OK — SMS promo applied');
                    }
                }
            }

        } catch (\RuntimeException $e) {

            // ════════════════════════════════════════════════════════════
            //  FATAL FAILURE
            //  - ICCID wapas PROVISIONED karo (retry ke liye)
            //  - Admin ko email bhejo
            //  - Exception re-throw (Job retry trigger karega)
            // ════════════════════════════════════════════════════════════

            $this->log('error', "ACTIVATION FAILED at step {$stepCount}", ['error' => $e->getMessage()]);

            // ICCID release — testing mode mein status touch nahi karte
            DB::table('ICCID')->where('id', $iccidRow->id)->update(['status' => 'PROVISIONED']);

            DB::table('orders')->where('id', $orderId)->update([
                'status' => 'IN PROGRESS',  // IN PROGRESS rakhho — FAILED nahi (abhi retry baaki)
                'inventoryId' => null,
                'msisdn' => '',
                'stepCount' => $stepCount,
            ]);

            $this->sendAdminFailureSummary($orderId, $stepCount, $e->getMessage());

            return ['success' => false, 'stepCount' => $stepCount, 'error' => $e->getMessage()];
        }

        // ════════════════════════════════════════════════════════════════
        //  ALL STEPS DONE — Mark ACTIVE
        // ════════════════════════════════════════════════════════════════

        DB::table('orders')->where('id', $orderId)->update([
            'status' => 'ACTIVE',
            'customerId' => $customerId,
            'subscriberId' => $subscriberId,
            'plan_moniker' => $planMoniker,
            'stepCount' => $stepCount,
        ]);

        // Testing ICCID — status ACTIVE mark nahi karte
        DB::table('ICCID')->where('id', $iccidRow->id)->update(['status' => 'ACTIVE']);

        $this->log('info', 'ACTIVATION SUCCESS ✅', [
            'order_id' => $orderId,
            'ICCID' => $ICCID,
            'customerId' => $customerId,
            'subscriberId' => $subscriberId,
            'total_steps' => $stepCount,
        ]);

        // Short URLs + QR code + Emails
        $this->generateShortUrls($orderId);
        $this->generateQrCode($orderId);
        $this->sendUserSuccessEmail($orderId);
        $this->sendAdminSuccessSummary($orderId, $stepCount);

        return ['success' => true, 'customerId' => $customerId, 'subscriberId' => $subscriberId];
    }


    public function rechargeEsim(int $orderId): array
    {
        $order = DB::table('orders')->where('id', $orderId)->first();

        Log::info('order:', ['order' => $order]);
        if (!$order) {
            throw new \RuntimeException("Recharge order not found: {$orderId}");
        }

        if ($order->orderType !== 'recharge') {
            throw new \RuntimeException("Order {$orderId} is not recharge type");
        }

        if (empty($order->subscriberId)) {
            throw new \RuntimeException("subscriberId missing for order {$orderId}");
        }

        $subscriberId = $order->subscriberId;

        $plan = DB::table('plans')->where('id', $order->plan_id)->first();

        if (!$plan) {
            throw new \RuntimeException("Plan not found for order {$orderId}");
        }

        $planMoniker = $plan->Moniker;

        Log::info('[eSIM Recharge] START', [
            'order_id' => $orderId,
            'subscriberId' => $subscriberId,
            'moniker' => $planMoniker,
            'mode' => 'PRODUCTION',
        ]);

        // 🔥 Apply promotion (real BST call)
        $req = $this->buildApplyPromoXml(
            $subscriberId,
            $planMoniker,
            date('Y-m-d') . ' 00:00:00Z'
        );

        $res = $this->callBstApi($req);

        Log::info('[eSIM Recharge] BST RESPONSE', [
            'order_id' => $orderId,
            'response' => substr($res, 0, 1000),
        ]);

        if ($this->hasError($res)) {
            throw new \RuntimeException('BST Recharge Error: ' . $this->extractError($res));
        }
        DB::table('orders')->where('id', $orderId)->update([
            'status' => 'ACTIVE',
            'esimLive' => '1',
            'apiResponse' => substr($res, 0, 5000),
            'plan_start_date' => '0000-00-00 00:00:00',
            'plan_end_date' => '0000-00-00 00:00:00',
            'loc_update_at' => now(),
        ]);

        Log::info('[eSIM Recharge] SUCCESS', [
            'order_id' => $orderId,
        ]);

        return ['success' => true];
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  QR CODE — existing esim_qrcode.php ko call karta hai
    // ═════════════════════════════════════════════════════════════════════════

    public function generateQrCode(int $orderId): void
    {
        $row = DB::table('orders as o')
            ->leftJoin('ICCID as i', 'i.id', '=', 'o.inventoryId')
            ->where('o.id', $orderId)
            ->select(
                'i.LPA_Value',
                'i.ICCID as iccid_val'
            )
            ->first();

        if (!$row || !$row->LPA_Value) {
            $this->log('warning', 'QR skipped — no LPA value');
            return;
        }

        $directory = public_path('images/uploads/qr');

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/' . $row->iccid_val . '.png';

        try {

            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($row->LPA_Value)
                ->size(350)
                ->margin(10)
                ->build();

            $result->saveToFile($filePath);

            $this->log('info', 'QR generated ✅', [
                'path' => $filePath
            ]);

        } catch (\Throwable $e) {

            $this->log('error', 'QR generation FAILED', [
                'error' => $e->getMessage()
            ]);
        }
    }




    public function getSubscriberBalance(string $subscriberId): array
    {
        // Load order data — we need msisdn + monikers, NOT just subscriberId
        $order = DB::table('orders as o')
            ->join('plans as p', 'o.plan_id', '=', 'p.id')
            ->where('o.subscriberId', $subscriberId)
            ->where('o.orderType', 'newsim')
            ->orderByDesc('o.id')
            ->select(
                'o.msisdn',
                'o.plan_moniker',
                'o.bonus_data',
                'o.Mins',
                'p.Moniker',
            )
            ->first();

        if (!$order) {
            throw new \Exception("No order found for subscriber: {$subscriberId}");
        }

        $msisdn = $order->msisdn;
        $monikerP = $order->Moniker;                              // primary plan
        $monikerB = $this->getBonusMoniker((int) ($order->bonus_data ?? 0)); // bonus data
        $monikerT = 'z1_100m';                                   // talktime moniker

        // ── 3 BST calls (same pattern as old balanceapi()) ────────────────────
        $primaryXml = $this->buildGetPromoStatusXml($msisdn, $monikerP);
        $bonusXml = $monikerB ? $this->buildGetPromoStatusXml($msisdn, $monikerB) : null;
        $talktimeXml = (int) ($order->Mins) === 100
            ? $this->buildGetPromoStatusXml($msisdn, $monikerT)
            : null;

        $resP = $this->callBstApi($primaryXml);
        $resB = $bonusXml ? $this->callBstApi($bonusXml) : null;
        $resT = $talktimeXml ? $this->callBstApi($talktimeXml) : null;

        Log::info('[eSIM] BALANCE RAW RESPONSES', [
            'subscriberId' => $subscriberId,
            'primary' => substr($resP, 0, 500),
            'bonus' => substr($resB ?? '', 0, 300),
            'talktime' => substr($resT ?? '', 0, 300),
        ]);

        // ── Parse primary plan ────────────────────────────────────────────────
        $result = [
            'data_total_gb' => 0,
            'data_rem_gb' => 0,
            'data_rem_mb' => 0,
            'bonus_total_gb' => 0,
            'bonus_rem_gb' => 0,
            'call_out_total' => 0,
            'call_out_rem' => 0,
            'call_in_total' => 0,
            'call_in_rem' => 0,
            'sms_total' => 0,
            'sms_rem' => 0,
            'start_time' => '',
            'end_time' => '',
            'source' => 'bst_live',
        ];

        $xmlP = @simplexml_load_string($resP);
        if ($xmlP) {
            foreach ($xmlP->xpath('//offer') as $offer) {
                $type = (string) $offer->{'call-type-group'};
                if ($type === 'data') {
                    $result['data_total_gb'] = round((int) $offer->{'initial-quantity'} / 1000, 2);
                    $result['data_rem_gb'] = round((int) $offer->{'remaining-quantity'} / 1000, 2);
                    $result['data_rem_mb'] = (int) $offer->{'remaining-quantity'};
                    $result['start_time'] = (string) $offer->{'start-time'};
                    $result['end_time'] = (string) $offer->{'end-time'};
                }
            }
        }

        // ── Parse bonus data ──────────────────────────────────────────────────
        if ($resB) {
            $xmlB = @simplexml_load_string($resB);
            if ($xmlB) {
                foreach ($xmlB->xpath('//offer') as $offer) {
                    if ((string) $offer->{'call-type-group'} === 'data') {
                        $result['bonus_total_gb'] = round((int) $offer->{'initial-quantity'} / 1000, 2);
                        $result['bonus_rem_gb'] = round((int) $offer->{'remaining-quantity'} / 1000, 2);
                    }
                }
            }
        }

        // ── Parse talktime ───────────────────────────────────────────────────
        if ($resT) {
            $xmlT = @simplexml_load_string($resT);
            if ($xmlT) {
                foreach ($xmlT->xpath('//offer') as $offer) {
                    $type = (string) $offer->{'call-type-group'};
                    if ($type === 'call-out') {
                        $result['call_out_total'] = (int) $offer->{'initial-quantity'};
                        $result['call_out_rem'] = (int) $offer->{'remaining-quantity'};
                    }
                    if ($type === 'call-in') {
                        $result['call_in_total'] = (int) $offer->{'initial-quantity'};
                        $result['call_in_rem'] = (int) $offer->{'remaining-quantity'};
                    }
                    if ($type === 'sms') {
                        $result['sms_total'] = (int) $offer->{'initial-quantity'};
                        $result['sms_rem'] = (int) $offer->{'remaining-quantity'};
                    }
                }
            }
        }

        return $result;
    }

    // ── New XML builder for get-promotion-status ──────────────────────────────
    private function buildGetPromoStatusXml(string $msisdn, string $moniker): string
    {
        $auth = $this->authXml();
        $msisdn = $this->xe($msisdn);
        $moniker = $this->xe($moniker);
        return <<<XML
<get-promotion-status version="1">
  {$auth}
  <number>{$msisdn}</number>
  <promotion>{$moniker}</promotion>
</get-promotion-status>
XML;
    }
    // ═════════════════════════════════════════════════════════════════════════
    //  USER SUCCESS EMAIL — SendGridService se
    // ═════════════════════════════════════════════════════════════════════════

    public function sendUserSuccessEmail(int $orderId): void
    {
        try {

            $row = DB::table('orders as o')
                ->join('plans as p', 'o.plan_id', '=', 'p.id')
                ->join('zones as z', 'z.id', '=', 'p.zone_id')
                ->leftJoin('ICCID as i', 'i.id', '=', 'o.inventoryId')
                ->where('o.id', $orderId)
                ->select(
                    'o.*',
                    'p.plan_name',
                    'p.USD as planCharge',
                    'z.zone_name',
                    'z.zone_name_il',
                    'z.zone_name_ar',
                    'z.zone_name_tr',
                    'i.ICCID as iccid_val',
                    'i.Camel_MSISDN',
                    'i.tly as apple_link',
                    'i.android_tly as android_link'
                )
                ->first();

            if (!$row) {
                $this->log('warning', 'User email skipped — row not found');
                return;
            }

            // Flood guard (1 hour max 2 emails per user)
            $recentCount = DB::table('orders')
                ->where('userId', $row->userId)
                ->where('orderType', 'newsim')
                ->where('date', '>=', now()->subHour())
                ->count();

            // if ($recentCount >= 2) {
            //     $this->log('info', 'User email suppressed — flood guard');
            //     return;
            // }

            $price = number_format((float) $row->planCharge, 2);

            // ✅ Correct QR path
            $qrUrl = config('app.url') . '/images/uploads/qr/' . $row->iccid_val . '.png';

            // Optional: agar QR file exist nahi karti toh warning log karo
            $qrFilePath = public_path('images/uploads/qr/' . $row->iccid_val . '.png');
            if (!file_exists($qrFilePath)) {
                $this->log('warning', 'QR image not found while sending email', [
                    'path' => $qrFilePath
                ]);
            }

            SendGridService::send(
                $row->email,
                self::TEMPLATE_USER_SUCCESS,
                [
                    'subject' => 'gsm2go eSIM: ' . $row->iccid_val . ' / ' . ($row->Camel_MSISDN ?? ''),

                    'fname' => $row->activationName ?? '',
                    'apple_link' => $row->apple_link ?? '',
                    'android_link' => $row->android_link ?? '',

                    'order_id' => $orderId,
                    'MSISDN' => $row->Camel_MSISDN ?? '',
                    'ICCID' => $row->iccid_val ?? '',

                    'zone_name' => $row->zone_name ?? '',
                    'zone_name_il' => $row->zone_name_il ?? '',
                    'zone_name_ar' => $row->zone_name_ar ?? '',
                    'zone_name_tr' => $row->zone_name_tr ?? '',

                    'plan_name' => ($row->plan_name ?? '') . ': $' . $price,
                    'total_charge' => '$' . $price,

                    'mode' => $row->source ?? 'airwallex',
                    'cust_id' => $row->customerId ?? '',
                    'subs_id' => $row->subscriberId ?? '',

                    'img' => $qrUrl,

                    'tt' => $row->Mins == 100 ? 'true' : 'false',
                    'tt_en' => $row->Mins == 100 ? 'Talk Time 100 Minutes: included' : '',

                    'ar' => $row->autorenew ? 'true' : 'false',
                    'autorenew_en' => $row->autorenew ? 'Auto Renew: $0' : '',

                    'bonus' => 'false',
                    'bonus_en' => '',
                    'b1g1' => 'false',
                    'b1g1_en' => '',
                    'disc' => 'false',
                    'disc_en' => '',

                    'show_msisdn' => $row->Mins >= 100 ? 'true' : 'false',
                    'show_msisdn_text' => $row->Mins >= 100 ? 'UK: ' . ($row->Camel_MSISDN ?? '') : '',

                    'status' => 'Active',
                    'total_paid' => '$' . $price,
                ]
            );

            $this->log('info', 'User success email sent ✅', [
                'to' => $row->email
            ]);

        } catch (\Throwable $e) {

            // Email kabhi activation ko crash nahi karega
            $this->log('error', 'User email FAILED (non-fatal)', [
                'error' => $e->getMessage()
            ]);
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  ADMIN EMAILS
    // ═════════════════════════════════════════════════════════════════════════

    private function sendAdminAlert(
        string $subject,
        string $stepName,
        string $request,
        string $response,
        string $note = ''
    ): void {
        try {
            SendGridService::send(
                $this->adminEmail,
                self::TEMPLATE_ADMIN_ALERT,
                [
                    'subject' => $subject,
                    'function' => $stepName,
                    'request' => substr($request, 0, 1500),
                    'response' => substr($response, 0, 1500),
                    'note' => $note,
                    'order_id' => $this->currentOrderId,
                ]
            );
        } catch (\Throwable $e) {
            $this->log('error', 'Admin alert email FAILED', ['error' => $e->getMessage()]);
        }
    }

    private function sendAdminFailureSummary(int $orderId, int $failedStep, string $errorMsg): void
    {
        try {
            $stepTable = collect($this->stepLog)
                ->map(fn($s) => "[Step {$s['step']}] {$s['name']}: {$s['status']}")
                ->implode("\n");

            SendGridService::send(
                $this->adminEmail,
                self::TEMPLATE_ADMIN_ALERT,
                [
                    'subject' => "🚨 eSIM Activation FAILED — Order #{$orderId} at Step {$failedStep}",
                    'function' => "activateNewEsim() — failed at step {$failedStep}",
                    'request' => "Order: #{$orderId} | UID: {$this->currentMasterUid}\n\nSteps:\n{$stepTable}",
                    'response' => $errorMsg,
                    'note' => 'Job will auto-retry up to 3 times. Check failed_jobs if still failing.',
                    'order_id' => $orderId,
                ]
            );
        } catch (\Throwable $e) {
            $this->log('error', 'Admin failure summary email FAILED', ['error' => $e->getMessage()]);
        }
    }

    private function sendAdminSuccessSummary(int $orderId, int $totalSteps): void
    {
        try {
            $stepTable = collect($this->stepLog)
                ->map(fn($s) => "[Step {$s['step']}] {$s['name']}: {$s['status']}")
                ->implode("\n");

            SendGridService::send(
                $this->adminEmail,
                self::TEMPLATE_ADMIN_ALERT,
                [
                    'subject' => "✅ eSIM Activated — Order #{$orderId}",
                    'function' => "activateNewEsim() — {$totalSteps} steps completed",
                    'request' => "Order: #{$orderId} | UID: {$this->currentMasterUid}\n\nSteps:\n{$stepTable}",
                    'response' => 'All steps OK. Status: ACTIVE.',
                    'note' => 'User has been emailed their QR code.',
                    'order_id' => $orderId,
                ]
            );
        } catch (\Throwable $e) {
            $this->log('error', 'Admin success summary email FAILED', ['error' => $e->getMessage()]);
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  SHORT URLS — t.ly
    // ═════════════════════════════════════════════════════════════════════════

    public function generateShortUrls(int $orderId): void
    {
        $row = DB::table('orders as o')
            ->leftJoin('ICCID as i', 'i.id', '=', 'o.inventoryId')
            ->where('o.id', $orderId)
            ->select('i.LPA_Value', 'i.ICCID as iccid_val')
            ->first();

        if (!$row?->LPA_Value)
            return;

        foreach (['apple', 'android'] as $platform) {
            $base = $platform === 'apple'
                ? 'https://esimsetup.apple.com/esim_qrcode_provisioning?carddata='
                : 'https://esimsetup.android.com/esim_qrcode_provisioning?carddata=';

            $longUrl = $base . rawurlencode($row->LPA_Value);
            $shortId = (string) ($orderId . rand(10000, 99999));

            try {
                $apiRes = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.tly.token'),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(10)->post('https://api.t.ly/api/v1/link/shorten', [
                            'long_url' => $longUrl,
                            'domain' => 'https://t.ly/',
                            'short_id' => $shortId,
                            'expire_at_datetime' => now()->addDays(700)->format('Y-m-d H:i:s'),
                            'description' => "eSIM #{$orderId} {$platform}",
                        ]);
                $shortUrl = $apiRes->json('short_url') ?? $longUrl;
            } catch (\Exception $e) {
                $this->log('warning', "t.ly failed for {$platform} — using full URL");
                $shortUrl = $longUrl; // fallback — email still works
            }

            $col = $platform === 'apple' ? 'tly' : 'android_tly';
            DB::table('ICCID')->where('ICCID', $row->iccid_val)->update([$col => $shortUrl]);
        }

        $this->log('info', 'Short URLs generated', ['order_id' => $orderId]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  PRIVATE — Logging
    // ═════════════════════════════════════════════════════════════════════════

    private function log(string $level, string $message, array $context = []): void
    {
        $context['order_id'] = $this->currentOrderId;
        $context['master_uid'] = $this->currentMasterUid;
        Log::$level("[eSIM] {$message}", $context);
    }

    /**
     * Har BST API step ke baad call karo
     * - DB mein log
     * - stepLog[] mein append (email summary ke liye)
     * - Laravel Log
     */
    private function recordStep(
        object $order,
        int $stepNumber,
        string $stepName,
        string $request,
        string $response
    ): void {
        $status = $this->hasError($response) ? 'FAILED ❌' : 'OK ✅';

        // DB
        DB::table('esimprocess_log')->insert([
            'user_id' => $order->userId,
            'plan_id' => $order->plan_id,
            'order_id' => $this->currentMasterUid,
            'process_date' => now(),
            'step_count' => $stepNumber,
            'api_request' => substr($request, 0, 5000),
            'api_response' => substr($response, 0, 5000),
            'order_type' => 'New SIM',
        ]);

        // In-memory (email ke liye)
        $this->stepLog[] = [
            'step' => $stepNumber,
            'name' => $stepName,
            'status' => $status,
        ];

        // Laravel log
        $logContext = ['order_id' => $this->currentOrderId, 'step' => $stepNumber];
        if ($status === 'OK ✅') {
            Log::info("[eSIM] [{$stepNumber}] {$stepName}: OK", $logContext);
        } else {
            Log::error("[eSIM] [{$stepNumber}] {$stepName}: FAILED", array_merge($logContext, [
                'response' => substr($response, 0, 500),
            ]));
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  PRIVATE — BST API
    // ═════════════════════════════════════════════════════════════════════════

    private function callBstApi(string $xml): string
    {
        // ✅ TEMP DEBUG — log FULL XML sent to BST
        Log::info('[eSIM] BST REQUEST FULL XML', [
            'order_id' => $this->currentOrderId,
            'xml' => $xml,
        ]);

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/xml'])
                ->withOptions(['verify' => true])
                ->timeout(30)
                ->withBody($xml, 'application/xml')
                ->post($this->apiUrl);

            if ($response->failed()) {
                throw new \RuntimeException('BST HTTP error: ' . $response->status());
            }

            $body = $response->body();

            Log::info('[eSIM] BST RESPONSE FULL', [
                'order_id' => $this->currentOrderId,
                'response' => $body,
            ]);

            return $body;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('BST connection failed: ' . $e->getMessage());
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  ✅ FIX 1 — hasError() now detects BST's <*-error> root tag pattern
    //             e.g. <create-customer-and-subscriber-error>
    // ═════════════════════════════════════════════════════════════════════════
    private function hasError(string $res): bool
    {
        if (empty(trim($res)))
            return true;

        // Standard BST error markers
        if (stripos($res, '<e>') !== false || stripos($res, 'error-code') !== false)
            return true;

        // ✅ BST returns a root tag ending in "-error" on failure
        // e.g. <create-customer-and-subscriber-error trid="...">MandatoryAPIParameterMissing name</...>
        if (preg_match('/<[a-z0-9-]+-error[\s>]/i', $res))
            return true;

        return false;
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  ✅ FIX 2 — extractError() now reads content from BST's <*-error> tags
    // ═════════════════════════════════════════════════════════════════════════
    private function extractError(string $res): string
    {
        try {
            $xml = @simplexml_load_string($res);
            if ($xml) {
                // Check standard error child elements
                $msg = (string) ($xml->error ?? $xml->{'error-message'} ?? '');
                if ($msg)
                    return $msg;

                // ✅ BST root tag IS the error — read its text content
                $rootName = $xml->getName();
                if (str_ends_with(strtolower($rootName), '-error')) {
                    return "{$rootName}: " . trim((string) $xml);
                }
            }
        } catch (\Exception $e) {
        }
        return substr($res, 0, 300);
    }

    private function parseXml(string $res, string $ctx): \SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($res);
        if ($xml === false) {
            $err = libxml_get_errors()[0]->message ?? 'parse error';
            libxml_clear_errors();
            throw new \RuntimeException("{$ctx}: Invalid XML — {$err}");
        }
        return $xml;
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  PRIVATE — Helpers
    // ═════════════════════════════════════════════════════════════════════════

    // ✅ FIX — Convert alpha-2 OR already-alpha-3 country codes to BST-accepted alpha-3
    private function toAlpha3(string $code): string
    {
        $code = strtoupper(trim($code));

        // Already alpha-3 — return as-is
        if (strlen($code) === 3)
            return $code;

        // Alpha-2 → Alpha-3 map (common countries)
        $map = [
            'AF' => 'AFG',
            'AL' => 'ALB',
            'DZ' => 'DZA',
            'AR' => 'ARG',
            'AM' => 'ARM',
            'AU' => 'AUS',
            'AT' => 'AUT',
            'AZ' => 'AZE',
            'BH' => 'BHR',
            'BD' => 'BGD',
            'BY' => 'BLR',
            'BE' => 'BEL',
            'BR' => 'BRA',
            'BG' => 'BGR',
            'CA' => 'CAN',
            'CN' => 'CHN',
            'CO' => 'COL',
            'HR' => 'HRV',
            'CY' => 'CYP',
            'CZ' => 'CZE',
            'DK' => 'DNK',
            'EG' => 'EGY',
            'EE' => 'EST',
            'ET' => 'ETH',
            'FI' => 'FIN',
            'FR' => 'FRA',
            'GE' => 'GEO',
            'DE' => 'DEU',
            'GH' => 'GHA',
            'GR' => 'GRC',
            'HK' => 'HKG',
            'HU' => 'HUN',
            'IN' => 'IND',
            'ID' => 'IDN',
            'IE' => 'IRL',
            'IL' => 'ISR',
            'IT' => 'ITA',
            'JP' => 'JPN',
            'JO' => 'JOR',
            'KZ' => 'KAZ',
            'KE' => 'KEN',
            'KW' => 'KWT',
            'KG' => 'KGZ',
            'LV' => 'LVA',
            'LB' => 'LBN',
            'LT' => 'LTU',
            'LU' => 'LUX',
            'MY' => 'MYS',
            'MV' => 'MDV',
            'MX' => 'MEX',
            'MD' => 'MDA',
            'MA' => 'MAR',
            'NP' => 'NPL',
            'NL' => 'NLD',
            'NZ' => 'NZL',
            'NG' => 'NGA',
            'NO' => 'NOR',
            'OM' => 'OMN',
            'PK' => 'PAK',
            'PH' => 'PHL',
            'PL' => 'POL',
            'PT' => 'PRT',
            'QA' => 'QAT',
            'RO' => 'ROU',
            'RU' => 'RUS',
            'SA' => 'SAU',
            'SG' => 'SGP',
            'SK' => 'SVK',
            'SI' => 'SVN',
            'ZA' => 'ZAF',
            'ES' => 'ESP',
            'LK' => 'LKA',
            'SE' => 'SWE',
            'CH' => 'CHE',
            'TW' => 'TWN',
            'TZ' => 'TZA',
            'TH' => 'THA',
            'TN' => 'TUN',
            'TR' => 'TUR',
            'UA' => 'UKR',
            'AE' => 'ARE',
            'GB' => 'GBR',
            'US' => 'USA',
            'UZ' => 'UZB',
            'VN' => 'VNM',
            'YE' => 'YEM',
            'ZM' => 'ZMB',
            'ZW' => 'ZWE',
        ];

        return $map[$code] ?? 'ISR'; // unknown fallback → ISR
    }

    private function formatPhone(string $phone, string $isdCode): string
    {
        $phone = trim($phone);
        if (strlen($phone) <= 10 && !str_starts_with($phone, '+'))
            $phone = $isdCode . $phone;
        return $phone;
    }

    private function getZoneCode(string $z): string
    {
        return match ($z) {
            '1' => 'EU', '2' => 'US', '4' => 'UAE',
            '7' => 'W', '8' => 'JP', '13' => 'UK', '22' => 'WU',
            default => 'ZN',
        };
    }

    private function getBonusMoniker(int $b): string
    {
        return match (true) {
            $b >= 50 => 'z1_50gb', $b >= 10 => 'z1_10gb',
            $b >= 5 => 'z1_5gb', $b >= 2 => 'z1_2gb',
            default => '',
        };
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  XML BUILDERS — BST API
    //  ✅ All values including credentials are XML-escaped via xe() helper
    // ═════════════════════════════════════════════════════════════════════════

    /** XML-escape a single value */
    private function xe(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** Encoded auth block — credentials may contain & < > chars */
    private function authXml(): string
    {
        $u = $this->xe($this->apiUser);
        $p = $this->xe($this->apiPassword);
        return "<authentication><username>{$u}</username><password>{$p}</password></authentication>";
    }

    private function buildCreateCustomerXml(array $d): string
    {
        // Encode every field
        foreach ($d as $k => $v) {
            $d[$k] = $this->xe((string) $v);
        }
        $auth = $this->authXml();
        $dist = $this->xe($this->distributorId);
        return <<<XML
<create-customer-and-subscriber version="1">
  {$auth}
  <customer>
    <name>{$d['name']} - {$d['msisdn']}</name>
    <customer-reference>Retail - {$d['ICCID']}</customer-reference>
    <distributor-id>{$dist}</distributor-id>
    <status>{$d['custStatus']}</status>
    <credit-basis>pre-paid</credit-basis><credit-limit>0</credit-limit>
    <warning-trigger>0</warning-trigger>
    <customer-group>norse</customer-group>
    <email-address>{$d['email']}</email-address>
    <contact-number>{$d['phone']}</contact-number>
    <address-line-1>{$d['displayPlan']}</address-line-1>
    <address-line-2>{$d['matchingCode']}</address-line-2>
    <address-line-3>{$d['arNotes']}</address-line-3>
    <address-line-4></address-line-4><postcode></postcode>
    <country>{$d['country']}</country>
    <notes>Order ID:{$d['orderId']}</notes>
  </customer>
  <subscriber>
    <first-name>{$d['fname']}</first-name>
    <middle-initials></middle-initials>
    <surname>{$d['lname']}</surname>
    <title>Mr</title><status>autoactivate</status>
    <enable-sip-registrations>no</enable-sip-registrations>
    <prefer-sip>no</prefer-sip>
    <voicemail-enabled>no</voicemail-enabled>
    <voicemail-timeout>30</voicemail-timeout>
    <notify-missed-calls>yes</notify-missed-calls>
    <send-charge-notifications>no</send-charge-notifications>
    <send-credit-notifications>no</send-credit-notifications>
    <forward-to></forward-to><withhold-cli>no</withhold-cli>
    <email-address></email-address>
    <subscriber-reference>{$d['msisdn']}</subscriber-reference>
    <forward-callback>no</forward-callback>
    <auto-cli>yes</auto-cli><block-gprs>no</block-gprs>
  </subscriber>
</create-customer-and-subscriber>
XML;
    }

    private function buildCreateSimXml(object $i): string
    {
        $auth = $this->authXml();
        $dist = $this->xe($this->distributorId);
        $iccid = $this->xe((string) $i->ICCID);
        $pin1 = $this->xe((string) $i->PIN1);
        $pin2 = $this->xe((string) $i->PIN2);
        $puk1 = $this->xe((string) $i->PUK1);
        $puk2 = $this->xe((string) $i->PUK2);
        $imsi = $this->xe((string) $i->Camel_IMSI);
        $msisdn = $this->xe((string) $i->Camel_MSISDN);
        return <<<XML
<create-sim version="1">
  {$auth}
  <distributorid>{$dist}</distributorid>
  <iccid>{$iccid}</iccid>
  <pin1>{$pin1}</pin1><pin2>{$pin2}</pin2>
  <puk1>{$puk1}</puk1><puk2>{$puk2}</puk2>
  <identity>
    <imsi>{$imsi}</imsi>
    <primary-msisdn>{$msisdn}</primary-msisdn>
    <secondary-msisdn></secondary-msisdn>
    <call-routing>pri-msrn</call-routing>
    <sms-routing>pri-msisdn</sms-routing>
  </identity>
</create-sim>
XML;
    }

    private function buildCreateDirXml(string $m): string
    {
        $auth = $this->authXml();
        $dist = $this->xe($this->distributorId);
        $msisdn = $this->xe($m);
        return <<<XML
<create-directory-number version="1">
  {$auth}
  <directory-number>{$msisdn}</directory-number>
  <directory-number-vendor>unknown</directory-number-vendor>
  <distributor-id>{$dist}</distributor-id>
  <supports-sms>yes</supports-sms><sms-home-routing>yes</sms-home-routing>
  <supports-voice>yes</supports-voice><allow-loopback>yes</allow-loopback>
  <hide>no</hide>
</create-directory-number>
XML;
    }

    private function buildAddDirXml(string $sub, string $m): string
    {
        $auth = $this->authXml();
        $sub = $this->xe($sub);
        $msisdn = $this->xe($m);
        return <<<XML
<add-subscriber-directory-number version="1">
  {$auth}
  <subscriberid>{$sub}</subscriberid>
  <directory-number>{$msisdn}</directory-number>
  <present-as-cli>yes</present-as-cli>
</add-subscriber-directory-number>
XML;
    }

    private function buildSetCliXml(string $sub, string $m): string
    {
        $auth = $this->authXml();
        $sub = $this->xe($sub);
        $msisdn = $this->xe($m);
        return <<<XML
<set-subscriber-cli version="1">
  {$auth}
  <subscriberid>{$sub}</subscriberid>
  <directory-number>{$msisdn}</directory-number>
</set-subscriber-cli>
XML;
    }

    private function buildAddSimXml(string $sub, string $iccid): string
    {
        $auth = $this->authXml();
        $sub = $this->xe($sub);
        $iccid = $this->xe($iccid);
        return <<<XML
<add-subscriber-sim version="1">
  {$auth}
  <subscriberid>{$sub}</subscriberid>
  <iccid>{$iccid}</iccid>
</add-subscriber-sim>
XML;
    }

    private function buildSetPasswordXml(string $sub, string $m): string
    {
        $auth = $this->authXml();
        $sub = $this->xe($sub);
        $msisdn = $this->xe($m);
        return <<<XML
<set-subscriber-password version="1">
  {$auth}
  <subscriberid>{$sub}</subscriberid>
  <username>{$msisdn}</username>
  <password>0000</password>
</set-subscriber-password>
XML;
    }

    private function buildApplyPromoXml(string $sub, string $moniker, string $startTime = ''): string
    {
        if (!$startTime)
            $startTime = date('Y-m-d') . ' 00:00:00Z';
        $auth = $this->authXml();
        $sub = $this->xe($sub);
        $moniker = $this->xe($moniker);
        $startTime = $this->xe($startTime);
        return <<<XML
<apply-promotion version="1">
  {$auth}
  <subscriberid>{$sub}</subscriberid>
  <promotion>{$moniker}</promotion>
  <start-time>{$startTime}</start-time>
  <notify-on-depletion>no</notify-on-depletion>
</apply-promotion>
XML;
    }
}