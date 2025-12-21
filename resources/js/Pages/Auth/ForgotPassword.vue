<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import Footer from '../User/Layouts/Footer.vue';

const { props } = usePage();
const status = defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />

    <!-- Navbar -->
    <nav class="fixed w-full bg-white border-gray-200 dark:bg-gray-900 z-50">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <div class="flex flex-row">
                <Link :href="route('user.home')" class="flex items-center mr-2">
                    <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">
                        T.shop
                    </span>
                </Link>
            </div>

            <div
                class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1"
                id="navbar-user"
            >
                <ul
                    class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 
                           md:flex-row md:space-x-8 md:mt-0 md:border-0 md:bg-white 
                           dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700"
                >
                    <li>
                        <Link
                            :href="route('user.home')"
                            class="block py-2 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100 
                                   md:hover:bg-transparent md:hover:text-blue-700 md:p-0 
                                   dark:text-white md:dark:hover:text-blue-500"
                        >
                            Головна
                        </Link>
                    </li>

                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Section -->
    <div
        class="min-h-screen py-60 flex items-center justify-center"
        style="background-image: linear-gradient(#93c5fd, #cbd5e1)"
    >
        <div
            class="w-10/12 lg:w-4/12 bg-white rounded-xl mx-auto shadow-lg overflow-hidden p-10"
        >
            <h2 class="text-3xl mb-2">Забули пароль?</h2>
            <p class="text-sm text-gray-600 mb-6">
                Не хвилюйтесь! Введіть свою електронну адресу, і ми надішлемо вам
                посилання для скидання пароля.
            </p>

    
            <form @submit.prevent="submit">
                <div>
                    <InputLabel for="email" value="Електронна пошта" />

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <Link
                        :href="route('login')"
                        class="text-sm text-gray-600 underline hover:text-gray-900"
                    >
                        Повернутись до входу
                    </Link>

                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Надіслати посилання
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </div>

    <Footer />
</template>
