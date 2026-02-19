<?php

return [

    /* ================= HERO ================= */
    'hero' => [
        'title' => 'eSIM Install and Setup Guide',
        'subtitle' => 'Under 5 Minutes',
        'description' => 'Please make sure you have a stable data connection, not limited by a company firewall (WiFi or 4G or 5G or LTE)',
    ],

    /* ================= TABS ================= */
    'tabs' => [
        'iphone' => 'iPhone',
        'android' => 'Android',
    ],

    /* ================= IPHONE ================= */
    'iphone' => [
        'title' => 'iPhone 11 or newer (unlocked)',

        'steps' => [
            [
                'title' => 'Scan QR Code',
                'items' => [
                    'Open the camera.',
                    'Scan the QR code (from your PC, tablet or another device).',
                    'Tap on [Cellular Plan] (yellow tag that appears after scanning).',
                ],
            ],
            [
                'title' => 'Install Cellular Plan Screen',
                'items' => [
                    'Tap on [Continue].',
                    'Your handset will verify the QR code.',
                    'WiFi or Cellular Data must be active.',
                ],
            ],
            [
                'title' => 'Add Cellular Plan',
                'items' => [
                    'Tap on [Add Cellular Plan].',
                ],
            ],
            [
                'title' => 'Cellular Plan Labels',
                'items' => [
                    'Label the new plan “gsm2go” (or any name you prefer).',
                    'Tap on [Continue].',
                ],
            ],
            [
                'title' => 'Default Line (Voice Calls)',
                'items' => [
                    'Use your home SIM for voice calls.',
                    'Change to gsm2go eSIM when you travel.',
                    'You can switch back and forth anytime.',
                    'Tap on [Continue].',
                ],
            ],
            [
                'title' => 'iMessage & FaceTime',
                'items' => [
                    'No need to change to your gsm2go eSIM.',
                    'Tap on [Continue].',
                ],
            ],
            [
                'title' => 'Cellular Data',
                'items' => [
                    'Select the gsm2go eSIM for cellular data when you travel.',
                    'Do not allow Cellular Data Switching to avoid roaming charges.',
                    'Tap on [Continue].',
                ],
            ],
            [
                'title' => 'Make Sure Everything Is Ready',
                'items' => [
                    'Go to Settings → Cellular.',
                    'gsm2go should be selected for Cellular Data and Voice (when traveling).',
                    'Under Cellular Plans, tap on gsm2go eSIM.',
                    'Switch Data Roaming ON.',
                ],
            ],
        ],
    ],

    /* ================= ANDROID ================= */
    'android' => [
        'title' => 'Android (Samsung S20 or newer, Google Pixel, others)',

        'steps' => [
            'Scan the QR code using your camera or:',
            'Go to Settings → Connections → SIM card manager.',
            'Tap Add mobile plan.',
            'Select Other ways to add plans → Add using QR code.',
            'Position the QR Code within the guided lines to scan.',
            'Add new mobile plan? Tap [Add].',
            'Turn on new mobile plan? Tap [OK].',
            'Once activated, view it in SIM card manager.',
            'Preferred SIM → Mobile Data → select your new eSIM.',
            'Go to Connections → Mobile networks.',
            'Switch Data roaming ON.',
            'If required, configure APN:',
            'APN Name: mobiledata',
            'APN: mobiledata',
            'Disable WiFi to test mobile data.',
            'Your new gsm2go eSIM is ready.',
        ],
    ],

    /* ================= DOWNLOAD ================= */
    'download' => [
        'title' => 'Download eSIM Install Guide',
        'button' => 'Download Guide',
    ],

];
