<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';

defineProps({
    productsByCategory: Array,

})

</script>

<template>
    <div id="default-carousel" class="relative w-full pb-10" data-carousel="slide">
        <!-- Carousel wrapper -->
        <div class="relative h-96 lg:h-[600px] overflow-hidden">
            <!-- Carousel Wrapper -->
            <div class="relative h-full w-full">
                <!-- Item 1 -->
                <div class="hidden duration-700 ease-in-out" data-carousel-item v-for="(image) in 7">
                    <img :src="`/product_images/Construction${image}.png`"
                        class="absolute w-full h-full lg:object-cover top-0 left-0 rounded-lg" alt="...">
                </div>
            </div>

            <!-- Slider indicators (inside the relative div) -->
            <div class="absolute z-30 flex bottom-5 left-1/2 -translate-x-1/2 space-x-3">
                <button type="button" class="w-3 h-3 bg-white rounded-full" aria-current="true" aria-label="Slide 1"
                    data-carousel-slide-to="0"></button>
                <button type="button" class="w-3 h-3 bg-white rounded-full" aria-current="false" aria-label="Slide 2"
                    data-carousel-slide-to="1"></button>
                <button type="button" class="w-3 h-3 bg-white rounded-full" aria-current="false" aria-label="Slide 3"
                    data-carousel-slide-to="2"></button>
                <button type="button" class="w-3 h-3 bg-white rounded-full" aria-current="false" aria-label="Slide 4"
                    data-carousel-slide-to="3"></button>
                <button type="button" class="w-3 h-3 bg-white rounded-full" aria-current="false" aria-label="Slide 5"
                    data-carousel-slide-to="4"></button>
                <button type="button" class="w-3 h-3 bg-white rounded-full" aria-current="false" aria-label="Slide 6"
                    data-carousel-slide-to="4"></button>
                <button type="button" class="w-3 h-3 bg-white rounded-full" aria-current="false" aria-label="Slide 7"
                    data-carousel-slide-to="4"></button>
            </div>
        </div>



    </div>

</template>