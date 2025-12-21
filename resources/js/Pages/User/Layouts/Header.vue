<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue'
import {
    Combobox,
    ComboboxInput,
    ComboboxOptions,
    ComboboxButton,
} from '@headlessui/vue'

const canLogin = usePage().props.canLogin;
const auth = usePage().props.auth;
let allProducts = ref([]);
const cart = computed(() => usePage().props.cart);

let selected = ref(allProducts[0])
let query = ref('')

const loadAllProducts = () => {
    axios
        .get('/products/all_products')
        .then((response) => {
            // Map the response data to match the Combobox items
            allProducts = response.data.allProducts;

        })
        .catch((error) => {
            console.error('Error loading products:', error);
        });
};

let filteredProducts = computed(() =>
    query.value === ''
        ? allProducts
        : allProducts.filter((product) => {
            const searchQuery = query.value.toLowerCase().replace(/\s+/g, '');
            const titleMatches = product.title
                .toLowerCase()
                .replace(/\s+/g, '')
                .includes(searchQuery);
            const vendorCodeMatches = product.vendor_code
                .toString()
                .includes(searchQuery);
            return titleMatches || vendorCodeMatches;
        })
);

onMounted(() => {
    loadAllProducts();
})

</script>
<template>
    <nav class="bg-black border-gray-800 dark:bg-black">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <div class="flex flex-row">
                <Link :href="route('user.home')"
                    class="flex items-center mr-2 transition-transform duration-300 hover:scale-105">
                <span
                    class="self-center text-2xl font-semibold whitespace-nowrap text-white hover:text-shadow-sm">T.shop</span>
                </Link>
            </div>

            <div v-if="canLogin" class="flex flex-row items-center md:order-3 p-1">
                <div class="mr-4">
                    <Link :href="route('cart.view')"
                        class="relative inline-flex items-center p-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition-transform duration-300 hover:scale-110">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                    <span class="sr-only">cart</span>
                    <div
                        class="absolute inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-red-500 border-2 border-white rounded-full -top-2 -right-2 dark:border-gray-900">
                        {{ cart.data.count }}</div>
                    </Link>
                </div>

                <button v-if="auth.user" type="button"
                    class="flex mr-3 text-sm bg-gray-800 rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600 transition-transform duration-300 hover:scale-110"
                    id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown"
                    data-dropdown-placement="bottom">
                    <span class="sr-only">Open user menu</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        class="w-8 h-8 rounded-full bg-white" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </button>

                <div v-else class="w-full">
                    <Link :href="route('login')" type="button"
                        class="text-white bg-gradient-to-br from-purple-600 to-blue-500 hover:bg-gradient-to-bl focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center mr-2 mb-2 transition-transform duration-300 hover:scale-110">
                    Login
                    </Link>
                </div>

                <!-- Dropdown menu -->
                <div v-if="auth.user"
                    class="z-50 hidden my-4 text-base list-none bg-gray-900 divide-y divide-gray-700 rounded-lg shadow dark:bg-gray-800 dark:divide-gray-600"
                    id="user-dropdown">
                    <div class="px-4 py-3">
                        <Link :href="route('profile.edit')" class="transition-transform duration-300 hover:scale-105">
                        <span class="block text-sm text-white dark:text-white hover:text-shadow-sm">{{ auth.user.name
                            }}</span>
                        </Link>
                        <span class="block text-sm text-gray-400 truncate dark:text-gray-400">{{ auth.user.email
                            }}</span>
                    </div>
                    <ul class="py-2" aria-labelledby="user-menu-button">
                        <li>
                            <Link :href="route('dashboard')"
                                class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white transition-transform duration-300 hover:scale-105 hover:text-shadow-sm">
                            Dashboard
                            </Link>
                        </li>
                        <li>
                            <Link :href="route('logout')" method="post"
                                class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white transition-transform duration-300 hover:scale-105 hover:text-shadow-sm">
                            Sign out
                            </Link>
                        </li>
                    </ul>
                </div>

                <button data-collapse-toggle="navbar-user" type="button"
                    class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-400 rounded-lg md:hidden hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600 transition-transform duration-300 hover:scale-110"
                    aria-controls="navbar-user" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 17 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M1 1h15M1 7h15M1 13h15" />
                    </svg>
                </button>
            </div>

            <div class="flex-grow md:flex-grow-0 w-full md:w-auto items-center justify-between hidden md:flex md:order-1"
                id="navbar-user">
                <ul
                    class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-700 rounded-lg bg-gray-900 md:flex-row md:space-x-8 md:mt-0 md:border-0 md:bg-black dark:bg-gray-900 dark:border-gray-700">
                    <li class="flex items-center">
                        <a href="#"
                            class="block py-2 pl-3 pr-4 text-white bg-blue-700 rounded md:bg-transparent md:text-blue-500 md:p-0 md:dark:text-blue-500 transition-transform duration-300 hover:scale-110 hover:text-shadow-sm"
                            aria-current="page">Головна</a>
                    </li>
                    <li class="flex items-center">
                        <a href="#"
                            class="block py-2 pl-3 pr-4 text-gray-300 rounded hover:bg-gray-700 md:hover:bg-transparent md:hover:text-blue-500 md:p-0 dark:text-white md:dark:hover:text-blue-500 dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent dark:border-gray-700 transition-transform duration-300 hover:scale-110 hover:text-shadow-sm">Доставка та оплата</a>
                    </li>
                    <li class="flex items-center">
                        <a href="#"
                            class="block py-2 pl-3 pr-4 text-gray-300 rounded hover:bg-gray-700 md:hover:bg-transparent md:hover:text-blue-500 md:p-0 dark:text-white md:dark:hover:text-blue-500 dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent dark:border-gray-700 transition-transform duration-300 hover:scale-110 hover:text-shadow-sm">Контакти</a>
                    </li>
                    <li class="transition-all duration-300"
                        :class="{ 'w-150': query.length > 0, 'w-100': query.length === 0 }">
                        <Combobox>
                            <div class="relative mt-1">
                                <div
                                    class="pe-20 relative flex items-center w-full cursor-default overflow-hidden rounded-lg text-left border-2 border-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/75 focus-visible:ring-offset-2 focus-visible:ring-offset-teal-300 sm:text-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-6 ml-2 text-gray-300">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                    </svg>
                                    <ComboboxInput
                                        class="w-full border-none py-2 pl-3 pr-10 text-sm leading-5 text-gray-300 bg-black focus:ring-0"
                                        @change="query = $event.target.value" placeholder="Пошук по сайту..." />
                                    <ComboboxButton class="absolute inset-y-0 right-0 flex items-center pr-2">
                                    </ComboboxButton>
                                </div>

                                <ComboboxOptions
                                    class="absolute mt-1 z-20 max-h-60 w-full overflow-auto rounded-md bg-gray-900 py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm">
                                    <div v-if="filteredProducts.length === 0 && query !== ''"
                                        class="relative cursor-default select-none px-4 py-2 text-gray-400">
                                        Nothing found.
                                    </div>

                                    <button v-for="product_search in filteredProducts.slice(0, 10)"
                                        :key="product_search.id" :value="product_search">
                                        <Link :href="route('product.show', product_search.id)">
                                        <div class="flex w-full shadow-inner m-1 overflow-hidden flex-col items-center relative cursor-default select-none p-2 border-2 border-gray-700 mb-1 mr-2 rounded transition-transform duration-300 hover:scale-105"
                                            :class="{
                                                'bg-teal-800 text-white': active,
                                                'text-gray-300 bg-gray-800': !active,
                                            }">
                                            <!-- Title: Ensure it takes full width -->
                                            <p class="mt-1 w-full"
                                                :class="{ 'font-medium': selected, 'font-normal': !selected }">
                                                {{ product_search.title }}
                                            </p>
                                            <!-- Image: Let the image scale without affecting the width -->
                                            <img v-if="product_search.product_images.length > 0"
                                                :src="product_search.product_images[0].image"
                                                class="w-16 md:w-32 max-w-full max-h-full" alt="Apple Watch">
                                        </div>
                                        </Link>
                                    </button>
                                </ComboboxOptions>
                            </div>
                        </Combobox>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>

<style>
.hover\:text-shadow-sm:hover {
    text-shadow: 0 0 3px rgba(255, 255, 255, 0.5);
}
</style>