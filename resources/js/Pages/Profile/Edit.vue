<script setup>

import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateEmailForm from './Partials/UpdateEmailForm.vue';
import { usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue'
import Header from '../User/Layouts/Header.vue';



defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    orders: Array,
});
const activeTab = ref('profile')
const orders = usePage().props.orders;

const showAll = ref(false)

const visibleOrders = computed(() => {
    return showAll.value ? orders : orders.slice(0, 4)
})

const pay = async (order) => {
    const response = await axios.post('/liqpay/getPaymentForm', {
        amount: order.total_price,
        order_id: order.id
    });
    const { data, signature } = response.data;

    if (!window.LiqPayCheckout) {
        const script = document.createElement('script');
        script.src = 'https://static.liqpay.ua/libjs/checkout.js';
        script.onload = () => launchLiqPay(data, signature);
        document.body.appendChild(script);
    } else {
        launchLiqPay(data, signature);
    }

    function launchLiqPay(data, signature) {
        const originalData = data;

        LiqPayCheckout.init({
            data,
            signature,
            embedTo: "#liqpay_placeholder",
            mode: "popup"
        }).on("liqpay.callback", function (data) {
            console.log(signature)

            console.log(location.hostname)
            if (location.hostname === '127.0.0.1') {
                console.log("Sending callback to server");
                axios.post('/liqpay/callback', {
                    data: originalData,
                    signature: signature,
                    info: data
                }).then(response => {
                    console.log('Callback POST success:', response.data);
                    if (response.status === 200) {
                        window.location.href = '/profile';
                    }
                }).catch(error => {
                    console.error('Callback POST failed:', error);
                });
            }
        }).on("liqpay.ready", function (data) {
            console.log("liqpay.ready", data);
        }).on("liqpay.close", function (data) {
            console.log("liqpay.close", data);
        });
    }
}


console.log(orders[1]);
</script>

<template>
    <Header />
    <div class="w-full p-6">
        <div class="flex space-x-2 mb-[-1px]">
            <button @click="activeTab = 'profile'" :class="[
                'px-4 py-2 rounded-t-lg border border-b-0',
                activeTab === 'profile'
                    ? 'bg-white text-blue-600 border-gray-300'
                    : 'bg-gray-100 text-gray-600 border-transparent'
            ]">
                Профіль
            </button>

            <button @click="activeTab = 'orders'" :class="[
                'px-4 py-2 rounded-t-lg border border-b-0',
                activeTab === 'orders'
                    ? 'bg-white text-blue-600 border-gray-300'
                    : 'bg-gray-100 text-gray-600 border-transparent'
            ]">
                Замовлення
            </button>
        </div>
        <!-- Tab Content Area -->
        <div class="w-full border border-gray-300 rounded-b-lg bg-white p-6">
            <div v-if="activeTab === 'profile'" class="space-y-6">
                <div class="max-w-3xl mx-auto space-y-6">
                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                    </div>

                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdateEmailForm :must-verify-email="mustVerifyEmail" :status="status" />
                    </div>

                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdatePasswordForm />
                    </div>

                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <DeleteUserForm />
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'orders'">
                <div
                    class="w-full p-4 bg-white border border-gray-200 rounded-lg shadow-sm sm:p-8 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h5 class="text-xl font-bold leading-none text-gray-900 dark:text-white">Останні замовлення
                        </h5>
                        <button @click="showAll = !showAll"
                            class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-500">
                            {{ showAll ? 'Сховати' : 'Показати всі' }}
                        </button>
                    </div>
                    <div class="flow-root relative">
                        <!-- Column names -->
                        <div class="flex items-center font-semibold text-gray-700 dark:text-gray-300 border-b pb-2">
                            <div class="shrink-1 w-8"></div>
                            <div class="flex-1 min-w-0 ms-4">Замовник</div>
                            <div class="flex-1 min-w-0 ms-4">Місто</div>
                            <div class="flex-1 min-w-0 ms-4">Адреса</div>
                            <div class="flex-1 min-w-0 ms-4">Дата</div>
                            <div class="w-24 text-right">Ціна</div>
                            <div class="flex-1 min-w-0 ms-4">Оплата</div>
                        </div>

                        <!-- Orders -->
                        <ul>
                            <li v-for="(order, index) in visibleOrders" :key="order.id" class="py-3 sm:py-4 border-b">
                                <div class="flex items-center">

                                    <div class="flex-1 min-w-0 ms-4">
                                        <p class="text-sm font-medium text-gray-900 truncate dark:text-white">
                                            {{ order.mobile_phone }}
                                        </p>
                                        <p class="text-sm text-gray-500 truncate dark:text-gray-400">
                                            {{ order.email }}
                                        </p>
                                    </div>
                                    <div class="flex-1 min-w-0 ms-4">
                                        {{ order.shipping_city }}
                                    </div>
                                    <div class="flex-1 min-w-0 ms-4">
                                        {{ order.shipping_warehouse }}
                                    </div>
                                    <div class="flex-1 min-w-0 ms-4">
                                        {{ new Date(order.updated_at).toLocaleDateString('en-GB').replace(/\//g, '-') }}
                                    </div>
                                    <div class="w-24 text-right font-semibold text-gray-900 dark:text-white">
                                        {{ order.total_price }} ₴
                                    </div>
                                    <div class="flex-1 min-w-0 ms-4">
                                        <div v-if="order.status === 'Paid'">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24">
                                                <path
                                                    d="M10.041 17l-4.5-4.319 1.395-1.435 3.08 2.937 7.021-7.183 1.422 1.409-8.418 8.591zm-5.041-15c-1.654 0-3 1.346-3 3v14c0 1.654 1.346 3 3 3h14c1.654 0 3-1.346 3-3v-14c0-1.654-1.346-3-3-3h-14zm19 3v14c0 2.761-2.238 5-5 5h-14c-2.762 0-5-2.239-5-5v-14c0-2.761 2.238-5 5-5h14c2.762 0 5 2.239 5 5z" />
                                            </svg>
                                        </div>
                                        <button v-if="order.status === 'Open'" @click="pay(order)" type="button"
                                            class="text-gray-900 bg-[#F7BE38] hover:bg-[#F7BE38]/90 focus:ring-4 focus:outline-none focus:ring-[#F7BE38]/50 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:focus:ring-[#F7BE38]/50 me-2 mb-2">
                                            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg"
                                                fill-rule="evenodd" clip-rule="evenodd" class="mr-1">
                                                <path
                                                    d="M22 3c.53 0 1.039.211 1.414.586s.586.884.586 1.414v14c0 .53-.211 1.039-.586 1.414s-.884.586-1.414.586h-20c-.53 0-1.039-.211-1.414-.586s-.586-.884-.586-1.414v-14c0-.53.211-1.039.586-1.414s.884-.586 1.414-.586h20zm1 8h-22v8c0 .552.448 1 1 1h20c.552 0 1-.448 1-1v-8zm-15 5v1h-5v-1h5zm13-2v1h-3v-1h3zm-10 0v1h-8v-1h8zm-10-6v2h22v-2h-22zm22-1v-2c0-.552-.448-1-1-1h-20c-.552 0-1 .448-1 1v2h22z" />
                                            </svg>
                                            Оплатити
                                        </button>
                                    </div>
                                </div>
                            </li>
                        </ul>


                        <div v-if="!showAll && orders.length > 4"
                            class="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-white dark:from-gray-800 pointer-events-none z-20">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>
