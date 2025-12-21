<script setup>
import Checkbox from '@/Components/Checkbox.vue';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import Footer from '../User/Layouts/Footer.vue';
import { computed } from 'vue';

const canLogin = usePage().props.canLogin;
const canRegister = usePage().props.canRegister;
const auth = usePage().props.auth;
const cart = computed(() => usePage().props.cart);

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <nav class="fixed w-full bg-white border-gray-200 dark:bg-gray-900">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <div class="flex flex-row">
                <Link :href="route('user.home')" class="flex items-center mr-2">
                <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">T.shop</span>
                </Link>

            </div>



            <div class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1" id="navbar-user">
                <ul
                    class="flex flex-col  font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:flex-row md:space-x-8 md:mt-0 md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700">
                    <li class="flex items-center">
                        <a href="#"
                            class="block py-2 pl-3 pr-4 text-white bg-blue-700 rounded md:bg-transparent md:text-blue-700 md:p-0 md:dark:text-blue-500"
                            aria-current="page">Головна</a>
                    </li>
                    <li class="flex items-center">
                        <a href="#"
                            class="block py-2 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700 md:p-0 dark:text-white md:dark:hover:text-blue-500 dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent dark:border-gray-700">Доставка та оплата</a>
                    </li>
                    <li class="flex items-center">
                        <a href="#"
                            class="block py-2 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700 md:p-0 dark:text-white md:dark:hover:text-blue-500 dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent dark:border-gray-700">Контакти</a>
                    </li>

                </ul>
            </div>
        </div>



    </nav>
    <div class="min-h-screen py-60" style="background-image: linear-gradient( #93c5fd,#cbd5e1)">
        <div class="container mx-auto">
            <div
                class="flex flex-col lg:flex-row w-10/12 lg:w-8/12 bg-white rounded-xl mx-auto shadow-lg overflow-hidden">
                <div class="w-full lg:w-1/2 flex flex-col items-center justify-center p-12 bg-no-repeat bg-cover bg-center"
                    style="background-image: url('product_images/Construction1.png');">

                </div>
                <div class="w-full lg:w-1/2 py-8 px-12">
                    <h2 class="text-3xl mb-2">Вхід</h2>

                    <form @submit.prevent="submit">
                        <Link v-if="canResetPassword" :href="route('register')"
                                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Не маєте акаунту?
                            </Link>
                        <div>
                            <InputLabel for="email" value="Електронна пошта"/>

                            <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                                autofocus autocomplete="username" />

                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="password" value="Пароль" />

                            <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password"
                                required autocomplete="current-password" />

                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="mt-4 block">
                            <label class="flex items-center">
                                <Checkbox name="remember" v-model:checked="form.remember" />
                                <span class="ms-2 text-sm text-gray-600">Запам'ятати мене</span>
                            </label>
                        </div>

                        <div class="mt-4 flex items-center justify-end">
                            <Link v-if="canResetPassword" :href="route('password.request')"
                                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Забули пароль?
                            </Link>
                            

                            <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing">
                                Увійти
                            </PrimaryButton>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
    </div>
    <Footer></Footer>
</template>