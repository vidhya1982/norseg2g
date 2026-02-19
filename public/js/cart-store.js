document.addEventListener("alpine:init", () => {
    Alpine.store("cart", {
        items: {},

        /* =====================
           INIT
        ===================== */
        init(items = {}) {
            this.items = items;
        },

        /* =====================
           CALCULATIONS
        ===================== */
        planTotal(item) {
            return item.price * item.quantity;
        },

        addonTotal(item) {
            if (item.addons?.talk_time?.enabled) {
                return item.addons.talk_time.price * item.addons.talk_time.qty;
            }
            return 0;
        },

        itemTotal(item) {
            return this.planTotal(item) + this.addonTotal(item);
        },

        get subtotal() {
            return Object.values(this.items).reduce(
                (sum, item) => sum + this.itemTotal(item),
                0,
            );
        },

        /* =====================
           COMMON QUANTITY HANDLER
           (PLAN + CART)
        ===================== */
        updateQuantity(key, type = "plan", action = "inc") {
            let item = this.items[key];

            if (!item) return;

            if (type === "plan") {
                if (action === "inc") item.quantity++;
                if (action === "dec") {
                    if (item.quantity > 1) item.quantity--;
                    else {
                        delete this.items[key];
                        Livewire.dispatch("remove-item", key);
                        return;
                    }
                }

                Livewire.dispatch("cart-sync", {
                    key,
                    data: { quantity: item.quantity },
                });
            }

            if (type === "talk_time") {
                if (action === "inc") item.addons.talk_time.qty++;
                if (action === "dec") {
                    if (item.addons.talk_time.qty > 1)
                        item.addons.talk_time.qty--;
                    else item.addons.talk_time.enabled = false;
                }

                Livewire.dispatch("cart-sync", {
                    key,
                    data: {
                        addons: {
                            talk_time: {
                                enabled: item.addons.talk_time.enabled,
                                qty: item.addons.talk_time.qty,
                            },
                        },
                    },
                });
            }
        },

        enableAddon(key, addon) {
            let item = this.items[key];

            if (!item.addons[addon]) return;

            item.addons[addon].enabled = true;

            // default qty
            if (addon === "talk_time" && !item.addons.talk_time.qty) {
                item.addons.talk_time.qty = 1;
            }

            Livewire.dispatch("cart-sync", {
                key,
                data: {
                    addons: {
                        [addon]: item.addons[addon],
                    },
                },
            });
        },

        disableAddon(key, addon) {
            let item = this.items[key];

            item.addons[addon].enabled = false;

            Livewire.dispatch("cart-sync", {
                key,
                data: {
                    addons: {
                        [addon]: { enabled: false },
                    },
                },
            });
        },

        /* =====================
           REMOVE ITEM
        ===================== */
        removeItem(key) {
            delete this.items[key];
            Livewire.dispatch("remove-item", key);
        },

        /* =====================
           PLAN PAGE STATE
        ===================== */
        qty: 1,
        planPrice: 0,

        addons: {
            talk_time: {
                enabled: true,
                qty: 1,
                price: 10,
            },
            auto_topup: {
                enabled: false,
            },
        },

        get total() {
            let total = this.planPrice * this.qty;

            if (this.addons.talk_time.enabled) {
                total +=
                    this.addons.talk_time.qty * this.addons.talk_time.price;
            }

            return total;
        },

        setPlanPrice(price) {
            this.planPrice = price;
        },

        resetPlan() {
            this.qty = 1;
            this.addons.auto_topup.enabled = false;
            this.addons.talk_time.enabled = true;
            this.addons.talk_time.qty = 1;
        },
    });
});
