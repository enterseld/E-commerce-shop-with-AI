<script setup>
import UserLayout from './Layouts/UserLayout.vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue'
import {
    Combobox,
    ComboboxInput,
    ComboboxOptions,
    ComboboxOption,
    ComboboxButton,
    TransitionRoot
} from '@headlessui/vue'
import { ChevronUpDownIcon, CheckIcon } from '@heroicons/vue/20/solid'
import Header from './Layouts/Header.vue';

const auth = usePage().props.auth;
const carts = computed(() => usePage().props.cart.data.items);
const products = computed(() => usePage().props.cart.data.products);
const total = computed(() => usePage().props.cart.data.total);
const itemId = (id) => carts.value.findIndex((item) => item.product_id == id);

// Reactive state
const cities = ref([])
const warehouses = ref([])
const selected = ref(null)
const selectedWarehouse = ref(null)
const query = ref('')
const queryWarehouse = ref('')
const citiesLoading = ref(false)
const warehousesLoading = ref(false)
const liqPay = ref('liqPay')
// Computed filtered arrays
const filteredCities = computed(() => {
    if (query.value === '') {
        return cities.value
    }

    const searchQuery = query.value.toLowerCase().replace(/\s+/g, '')
    return cities.value.filter((city) => {
        const titleNormalized = city.title
            .toLowerCase()
            .replace(/\s+/g, '')
            .replace(/\(.*?\)/g, '')
        return titleNormalized.includes(searchQuery)
    })
})

const filteredWarehouses = computed(() => {
    if (queryWarehouse.value === '') {
        return warehouses.value
    }

    const searchQuery = queryWarehouse.value.toLowerCase().replace(/\s+/g, '')
    return warehouses.value.filter((warehouse) => {
        const titleNormalized = warehouse.title
            .toLowerCase()
            .replace(/\s+/g, '')
            .replace(/\(.*?\)/g, '')
        return titleNormalized.includes(searchQuery)
    })
})

// Event handlers
const handleCityInput = (event) => {
    query.value = event.target.value
}

const handleWarehouseInput = (event) => {
    queryWarehouse.value = event.target.value
}

// API functions with error handling and loading states
const loadCities = async () => {
    try {
        citiesLoading.value = true
        const response = await axios.get('/getCities/0')
        console.log("Response", response)
        cities.value = response.data.cities || []
    } catch (error) {
        console.error('Error loading cities:', error)
        cities.value = []
    } finally {
        citiesLoading.value = false
    }
}

const loadWarehouses = async (cityTitle) => {
    if (!cityTitle) {
        warehouses.value = []
        return
    }

    try {
        warehousesLoading.value = true
        const response = await axios.get(`/getWarehouses/${encodeURIComponent(cityTitle)}/0`)
        warehouses.value = response.data.warehouses || []
    } catch (error) {
        console.error('Error loading warehouses:', error)
        warehouses.value = []
    } finally {
        warehousesLoading.value = false
    }
}

// Watchers
watch(selected, (newCity) => {
    // Reset warehouse selection when city changes
    selectedWarehouse.value = null
    queryWarehouse.value = ''

    if (newCity?.title) {
        loadWarehouses(newCity.title)
    } else {
        warehouses.value = []
    }
})

let phone_number = ref("");
let first_name = ref("");
let last_name = ref("");
let middle_name = ref("");
let email = ref("");
if (auth.user) {
    first_name.value = auth.user.first_name;
    last_name.value = auth.user.last_name;
    middle_name.value = auth.user.middle_name;
}

const remove = (product) =>
    router.delete(route('cart.delete', product), { preserveState: true, replace: true });


    
let sendOrder = async () => {
    if (!validate()) {
        if (auth.user)
            Swal.fire({
                text: "Будь ласка, введіть правильний номер телефону та ПІБ",
                icon: "warning"
            })
        else Swal.fire({
            text: "Будь ласка, введіть правильний номер телефону, email та ПІБ",
            icon: "warning"
        })
        return;
    }

    const formOrder = new FormData();
    formOrder.append('first_name', first_name.value);
    formOrder.append('last_name', last_name.value);
    formOrder.append('middle_name', middle_name.value);
    formOrder.append('total_price', total.value);
    formOrder.append('mobile_phone', phone_number.value);
    formOrder.append('shipping_city', selected.value.title);
    formOrder.append('shipping_warehouse', selectedWarehouse.value.title);
    formOrder.append('products', JSON.stringify(usePage().props.cart.data.items));
    console.log(usePage().props.cart.data.items)
    const userId = auth.user ? usePage().props.auth.user.id : 0;
    const emailToSend = auth.user ? usePage().props.auth.user.email : email.value;
    formOrder.append('email', emailToSend);
    formOrder.append('user_id', userId);

    const formAdress = new FormData();
    formAdress.append('shipping_city', selected.value.title);
    formAdress.append('shipping_warehouse', selectedWarehouse.value.title);
    formAdress.append('user_id', userId);

    try {
        const orderResponse = await axios.post('/orders/store', formOrder, {
            headers: {
                'Content-Type': 'multipart/form-data',
            }
        });

        if (auth.user) {
            await axios.post('/adresses/store', formAdress, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                }
            });
        }


        const orderId = orderResponse.data.order_id;
        if (liqPay.value === 'liqPay') {
            const response = await axios.post('/liqpay/getPaymentForm', {
                amount: total.value,
                order_id: orderId
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
                                window.location.href = '/dashboard';
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
        else {
            window.location.href = '/dashboard';

        }
    }
    catch (err) {
        console.error('An error occurred:', err);
    }
};

let validate = () => {
    let patternPhone1 = /^\+?\d{1,3}\d{9}$/;
    let patternPhone2 = /^\d{10}$/;
    let patternEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if ((patternPhone1.test(phone_number.value) || patternPhone2.test(phone_number.value)) && (patternEmail.test(email.value) || auth.user) && first_name.value != "" && last_name.value != "" && middle_name.value != "") {

        return true;
    }
    else {
        console.log("not passed")
        return false;
    }
}

const check = () => {
    console.log(liqPay.value)
}

// Initialize on mount
onMounted(() => {
    loadCities()
})

// Expose selected values for parent component
defineExpose({
    selectedCity: selected,
    selectedWarehouse
})


</script>
<template>

    <Header></Header>
        <section
            class="bg-white text-gray-600 body-font relative mx-auto max-w-1xl px-4 py-6 sm:px-6 sm:py-24 lg:max-w-screen-1xl lg:px-8">
            <div
                class="container px-5 py-3 mx-auto flex flex-wrap lg:flex-col lg:space-y-4 xl:flex-row xl:space-y-0 xl:space-x-4 justify-center ">

                <!--List of cart items-->
                <div class="overflow-x-auto">
                    <table
                        class="w-full text-xs md:text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 justify-center">
                        <thead
                            class="text-[10px] md:text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">

                            <tr>
                                <th scope="col" class="px-16 py-3 text-center w-2/11 ">
                                    <span class="sr-only">Image</span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-center w-2/11 font-medium">
                                    Продукт
                                </th>
                                <th scope="col" class="px-6 py-3 text-center w-2/11 font-medium">
                                    Кількість
                                </th>
                                <th scope="col" class="px-6 py-3 text-center w-2/11 font-medium">
                                    Ціна
                                </th>
                                <th scope="col" class="px-6 py-3 text-center w-2/11 font-medium">
                                    Дії
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in products" :key="product.id"
                                class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="p-4">
                                    <img v-if="product.product_images.length > 0" :src="product.product_images[0].image"
                                        class="w-16 md:w-32 max-w-full max-h-full" alt="Apple Watch">
                                    <img v-else
                                        src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/65/No-Image-Placeholder.svg/330px-No-Image-Placeholder.svg.png"
                                        class="w-16 md:w-32 max-w-full max-h-full" alt="Apple Watch">
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    {{ product.title }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <button @click.prevent="update(product, carts[itemId(product.id)].quantity - 1)"
                                            :disabled="carts[itemId(product.id)].quantity <= 1"
                                            :class="[carts[itemId(product.id)].quantity > 1 ? 'cursor-pointer text-purple-600' : 'cursor-not-allowed text-gray-300 dark:text-gray-500', 'inline-flex items-center justify-center p-1 me-3 text-sm font-medium h-6 w-6 text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700']"
                                            class="" type="button">

                                            <span class="sr-only">Quantity button</span>
                                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 18 2">
                                                <path stroke="currentColor" stroke-linecap="round"
                                                    stroke-linejoin="round" stroke-width="2" d="M1 1h16" />
                                            </svg>
                                        </button>
                                        <div>
                                            <input type="number" id="first_product"
                                                v-model="carts[itemId(product.id)].quantity" min="1"
                                                class="bg-gray-50 w-14 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500"
                                                placeholder="1" required />
                                        </div>
                                        <button @click.prevent="update(product, carts[itemId(product.id)].quantity + 1)"
                                            class="inline-flex items-center justify-center h-6 w-6 p-1 ms-3 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                                            type="button">
                                            <span class="sr-only">Quantity button</span>
                                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 18 18">
                                                <path stroke="currentColor" stroke-linecap="round"
                                                    stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    {{ product.price }} грн.
                                </td>
                                <td class="px-6 py-4">
                                    <a href="#" @click="remove(product)"
                                        class="font-medium text-red-600 dark:text-red-500 hover:underline">Прибрати</a>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>

            </div>

            <div
                class="container px-5  mx-auto flex flex-wrap justify-center lg:flex-col lg:space-y-4 xl:flex-row xl:space-y-0 xl:space-x-4">
                <div
                    class="lg:w-2/3 md:w-4/4 bg-white flex-col ml-50 md:py-8 mt-8 ml-5 md:mt-0 w-full sm:mt-4 lg:inline-block xl:block ">
                    <h2 class="text-gray-900 text-lg mb-1 font-medium title-font mx-auto">Підсумок</h2>
                    <p class="leading-relaxed text-lg mb-5 text-gray-600">Загалом: {{ total }} грн</p>
                    <div class="flex flex-wrap -mx-3 mb-2">
                        <div class="w-full md:w-full px-3 mb-6 md:mb-0 flex">
                            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                                for="grid-city">
                            </label>
                            <div class="relative z-0 w-full mb-5 group">
                                <input type="name" name="floating_name" id="floating_name" v-model="first_name"
                                    class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 peer"
                                    placeholder=" " required />
                                <label for="floating_name"
                                    class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Ім'я</label>
                            </div>
                            <div class="relative z-0 w-full mb-5 ml-5 group">
                                <input type="last_name" name="floating_last_name" id="floating_last_name"
                                    v-model="last_name"
                                    class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 peer"
                                    placeholder=" " required />
                                <label for="floating_last_name"
                                    class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Прізвище</label>
                            </div>
                            <div class="relative z-0 w-full mb-5 ml-5 group">
                                <input type="middle_name" name="floating_middle_name" id="floating_middle_name"
                                    v-model="middle_name"
                                    class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 peer"
                                    placeholder=" " required />
                                <label for="floating_middle_name"
                                    class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">По-батькові</label>
                            </div>
                        </div>

                    </div>
                    <div>
                        <div class="flex flex-wrap -mx-3 mb-6">
                            <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                                <Combobox v-model="selected">
                                    <div class="relative z-40 w-full mb-5 group">
                                        <div class="relative">
                                            <ComboboxInput id="floating_city" name="floating_city"
                                                class="peer block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0"
                                                :displayValue="(city) => city?.title || ''" @input="handleCityInput"
                                                placeholder=" " required />

                                            <label for="floating_city"
                                                class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">
                                                Місто
                                            </label>

                                            <ComboboxButton
                                                class="absolute inset-y-0 right-0 flex items-center pr-2 z-20">
                                                <ChevronUpDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                            </ComboboxButton>
                                        </div>

                                        <TransitionRoot leave="transition ease-in duration-100" leaveFrom="opacity-100"
                                            leaveTo="opacity-0" @after-leave="query = ''">
                                            <ComboboxOptions
                                                class="absolute mt-1 z-50 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm">
                                                <div v-if="filteredCities.length === 0 && query !== ''"
                                                    class="relative cursor-default select-none px-4 py-2 text-gray-700">
                                                    Нічого не знайдено.
                                                </div>

                                                <ComboboxOption v-for="city in filteredCities.slice(0, 20)"
                                                    :key="city.id" :value="city" v-slot="{ selected, active }">
                                                    <li class="relative cursor-default select-none py-2 pl-10 pr-4"
                                                        :class="{
                                                            'bg-teal-600 text-white': active,
                                                            'text-gray-900': !active,
                                                        }">
                                                        <span class="block truncate"
                                                            :class="{ 'font-medium': selected, 'font-normal': !selected }">
                                                            {{ city.title }}
                                                        </span>
                                                        <span v-if="selected"
                                                            class="absolute inset-y-0 left-0 flex items-center pl-3"
                                                            :class="{ 'text-white': active, 'text-teal-600': !active }">
                                                            <CheckIcon class="h-5 w-5" aria-hidden="true" />
                                                        </span>
                                                    </li>
                                                </ComboboxOption>
                                            </ComboboxOptions>
                                        </TransitionRoot>
                                    </div>
                                </Combobox>
                            </div>

                            <!-- Warehouse Selection -->
                            <div class="w-full md:w-2/3 px-3">
                                <Combobox v-model="selectedWarehouse">
                                    <div class="relative z-30 w-full mb-5 group">
                                        <div class="relative">
                                            <ComboboxInput id="floating_warehouse" name="floating_warehouse"
                                                class="peer block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0"
                                                :displayValue="(warehouse) => warehouse?.title || ''"
                                                @input="handleWarehouseInput" :disabled="!selected || warehousesLoading"
                                                placeholder=" " required />

                                            <label for="floating_warehouse"
                                                class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">
                                                Назва або номер відділення
                                                <span v-if="warehousesLoading" class="text-xs">(завантаження...)</span>
                                            </label>

                                            <ComboboxButton
                                                class="absolute inset-y-0 right-0 flex items-center pr-2 z-20"
                                                :disabled="!selected || warehousesLoading">
                                                <ChevronUpDownIcon class="h-5 w-5"
                                                    :class="warehousesLoading ? 'text-gray-300 animate-spin' : 'text-gray-400'" />
                                            </ComboboxButton>
                                        </div>

                                        <TransitionRoot leave="transition ease-in duration-100" leaveFrom="opacity-100"
                                            leaveTo="opacity-0" @after-leave="queryWarehouse = ''">
                                            <ComboboxOptions
                                                class="absolute mt-1 z-40 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm">
                                                <div v-if="filteredWarehouses.length === 0 && queryWarehouse !== '' && !warehousesLoading"
                                                    class="relative cursor-default select-none px-4 py-2 text-gray-700">
                                                    Нічого не знайдено.
                                                </div>

                                                <div v-if="warehousesLoading"
                                                    class="relative cursor-default select-none px-4 py-2 text-gray-500">
                                                    Завантаження відділень...
                                                </div>

                                                <ComboboxOption v-for="warehouse in filteredWarehouses.slice(0, 20)"
                                                    :key="warehouse.id" :value="warehouse"
                                                    v-slot="{ selected: warehouseSelected, active }">
                                                    <li class="relative cursor-default select-none py-2 pl-10 pr-4"
                                                        :class="{
                                                            'bg-teal-600 text-white': active,
                                                            'text-gray-900': !active,
                                                        }">
                                                        <span class="block"
                                                            :class="{ 'font-medium': warehouseSelected, 'font-normal': !warehouseSelected }">
                                                            {{ warehouse.title }}
                                                        </span>
                                                        <span v-if="warehouseSelected"
                                                            class="absolute inset-y-0 left-0 flex items-center pl-3"
                                                            :class="{ 'text-white': active, 'text-teal-600': !active }">
                                                            <CheckIcon class="h-5 w-5" aria-hidden="true" />
                                                        </span>
                                                    </li>
                                                </ComboboxOption>
                                            </ComboboxOptions>
                                        </TransitionRoot>
                                    </div>
                                </Combobox>
                            </div>
                        </div>

                    </div>


                    <div class="relative z-30 w-full mb-5 group" v-if="!auth.user">
                        <input type="email" name="floating_email" id="floating_phone_number" v-model="email"
                            class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 peer"
                            placeholder=" " required />
                        <label for="email"
                            class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Електронна
                            пошта</label>
                    </div>

                    <div class="relative z-0 w-full mb-5 group">
                        <input type="phone_number" name="floating_phone_number" id="floating_phone_number"
                            v-model="phone_number"
                            class="block py-2.5  px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 peer"
                            placeholder=" " required />
                        <label for="phone_number"
                            class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Номер
                            телефону (+380123456789)</label>
                    </div>

                    <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">Оплата</h3>
                    <ul
                        class="items-center w-full text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg sm:flex dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <li class="w-full border-b border-gray-200 sm:border-b-0 sm:border-r dark:border-gray-600">
                            <div class="flex items-center ps-3">
                                <input id="liqPay" type="radio" value="liqPay" name="list-radio" v-model="liqPay"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="liqpay"
                                    class="w-full py-3 ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Онлайн
                                    через сервіс LiqPay</label>
                            </div>
                        </li>
                        <li class="w-full border-b border-gray-200 sm:border-b-0 sm:border-r dark:border-gray-600">
                            <div class="flex items-center ps-3">
                                <input id="card" type="radio" value="card" name="list-radio" v-model="liqPay"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="card"
                                    class="w-full py-3 ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Оплата
                                    на картку</label>
                            </div>
                        </li>
                        <li class="w-full border-b border-gray-200 sm:border-b-0 sm:border-r dark:border-gray-600">
                            <div class="flex items-center ps-3">
                                <input id="novapay" type="radio" value="novapay" name="list-radio" v-model="liqPay"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="novapay"
                                    class="w-full py-3 ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">NovaPay
                                    (додаткова комісія)</label>
                            </div>
                        </li>
                        <li class="w-full border-b border-gray-200 sm:border-b-0 sm:border-r dark:border-gray-600">
                            <div class="flex items-center ps-3">
                                <input id="later" type="radio" value="later" name="list-radio" v-model="liqPay"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                <label for="later"
                                    class="w-full py-3 ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Пізніше
                                    у персональному кабінеті</label>
                            </div>
                        </li>
                    </ul>

                    <div class="flex items-center mb-4 mt-4">
                        <input id="checkbox-1" type="checkbox" value="0"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="checkbox-1" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Я
                            погоджуюся із <a href="#" class="text-blue-600 hover:underline dark:text-blue-500">умовами
                                обслуговування</a>.</label>
                    </div>


                    <button type="submit"
                        class="text-white bg-indigo-500 border-0 py-2 px-6 focus:outline-none hover:bg-indigo-600 rounded text-lg"
                        @click="sendOrder()">
                        Checkout
                    </button>
                    <p class="text-xs text-gray-500 mt-3">Continue shopping</p>
                </div>

            </div>

        </section>



</template>
