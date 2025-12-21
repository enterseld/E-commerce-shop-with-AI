<script setup>
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

defineProps({
    orders: Array
});

// Modal state
const showModal = ref(false);
const selectedOrder = ref(null);

// View order details
const viewOrder = (orderId) => {
    const orders = usePage().props.orders;
    selectedOrder.value = orders.find(order => order.id === orderId);
    showModal.value = true;
};

// Close modal
const closeModal = () => {
    showModal.value = false;
    selectedOrder.value = null;
};

// Format date
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('uk-UA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Format price
const formatPrice = (price) => {
    return new Intl.NumberFormat('uk-UA', {
        style: 'currency',
        currency: 'UAH'
    }).format(price);
};

// Get status badge color
const getStatusColor = (status) => {
    const colors = {
        'Open': 'bg-blue-700 hover:bg-blue-800 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800',
        'Processing': 'bg-yellow-700 hover:bg-yellow-800 focus:ring-yellow-300 dark:bg-yellow-600 dark:hover:bg-yellow-700 dark:focus:ring-yellow-800',
        'Completed': 'bg-green-700 hover:bg-green-800 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800',
        'Cancelled': 'bg-red-700 hover:bg-red-800 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800',
    };
    return colors[status] || 'bg-gray-700 hover:bg-gray-800 focus:ring-gray-300 dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800';
};

// Get status badge color (simple version for modal)
const getStatusBadgeColor = (status) => {
    const colors = {
        'Open': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'Processing': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'Completed': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'Cancelled': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
};
</script>

<template>
    <section class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5">
        <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                    <div class="w-full md:w-1/2">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Управління замовленнями</h2>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3">№ Замовлення</th>
                                <th scope="col" class="px-4 py-3">Ім'я клієнта</th>
                                <th scope="col" class="px-4 py-3">Email</th>
                                <th scope="col" class="px-4 py-3">Телефон</th>
                                <th scope="col" class="px-4 py-3">Сума</th>
                                <th scope="col" class="px-4 py-3">Статус</th>
                                <th scope="col" class="px-4 py-3">Місто</th>
                                <th scope="col" class="px-4 py-3">Дата</th>
                                <th scope="col" class="px-4 py-3">Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in usePage().props.orders" :key="order.id" class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    #{{ order.id }}
                                </th>
                                <td class="px-4 py-3">
                                    {{ order.first_name }} {{ order.middle_name }} {{ order.last_name }}
                                </td>
                                <td class="px-4 py-3">{{ order.email || 'Н/Д' }}</td>
                                <td class="px-4 py-3">{{ order.mobile_phone }}</td>
                                <td class="px-4 py-3 font-semibold">{{ formatPrice(order.total_price) }}</td>
                                <td class="px-4 py-3">
                                    <button type="button" :class="getStatusColor(order.status)"
                                        class="px-3 py-2 text-xs font-medium text-center text-white rounded-lg focus:ring-4 focus:outline-none">
                                        {{ order.status }}
                                    </button>
                                </td>
                                <td class="px-4 py-3">{{ order.shipping_city }}</td>
                                <td class="px-4 py-3">{{ formatDate(order.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <button @click="viewOrder(order.id)" type="button"
                                        class="px-3 py-2 text-xs font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                        Переглянути
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Order Details Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModal"></div>
            
            <!-- Modal Content -->
            <div class="relative w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="relative bg-white rounded-lg shadow dark:bg-gray-800">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-4 border-b dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Деталі замовлення #{{ selectedOrder?.id }}
                        </h3>
                        <button @click="closeModal" type="button" 
                            class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Закрити</span>
                        </button>
                    </div>
                    
                    <!-- Modal Body -->
                    <div class="p-6 space-y-6" v-if="selectedOrder">
                        <!-- Customer Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Інформація про клієнта</h4>
                                <div class="space-y-2 text-sm">
                                    <p class="text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">ПІБ:</span> 
                                        {{ selectedOrder.last_name }} {{ selectedOrder.first_name }} {{ selectedOrder.middle_name }}
                                    </p>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Email:</span> 
                                        {{ selectedOrder.email || 'Н/Д' }}
                                    </p>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Телефон:</span> 
                                        {{ selectedOrder.mobile_phone }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Інформація про доставку</h4>
                                <div class="space-y-2 text-sm">
                                    <p class="text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Місто:</span> 
                                        {{ selectedOrder.shipping_city }}
                                    </p>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Відділення:</span> 
                                        {{ selectedOrder.shipping_warehouse }}
                                    </p>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Статус:</span>
                                        <span :class="getStatusBadgeColor(selectedOrder.status)" class="ml-2 px-2.5 py-0.5 rounded text-xs font-medium">
                                            {{ selectedOrder.status }}
                                        </span>
                                    </p>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        <span class="font-medium">Дата:</span> 
                                        {{ formatDate(selectedOrder.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Товари замовлення</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-4 py-3">Товар</th>
                                            <th scope="col" class="px-4 py-3">Артикул</th>
                                            <th scope="col" class="px-4 py-3">Ціна</th>
                                            <th scope="col" class="px-4 py-3">Кількість</th>
                                            <th scope="col" class="px-4 py-3">Сума</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in selectedOrder.order_items" :key="item.id" 
                                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                                {{ item.product?.title || 'Товар видалено' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                {{ item.vendor_code || item.product?.vendor_code || 'Н/Д' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                {{ formatPrice(item.product?.price) }}
                                            </td>
                                            <td class="px-4 py-3">
                                                {{ item.quantity }}
                                            </td>
                                            <td class="px-4 py-3 font-semibold">
                                                {{ formatPrice(item.product?.price * item.quantity) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-gray-50 dark:bg-gray-700">
                                            <td colspan="4" class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">
                                                Разом:
                                            </td>
                                            <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                                {{ formatPrice(selectedOrder.total_price) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end p-4 border-t dark:border-gray-600">
                        <button @click="closeModal" type="button" 
                            class="px-5 py-2.5 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                            Закрити
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>