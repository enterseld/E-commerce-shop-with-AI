<script setup>
import { onMounted } from 'vue';
import { initFlowbite } from 'flowbite';
import Header from './Header.vue';
import Footer from './Footer.vue';

import ChatPopup from './ChatPopup.vue';
onMounted(() => {
    initFlowbite();
})

</script>

<template>
    <!--Header-->
    <Header></Header>
    <!--End-->

    <!--Main content-->
    <div class="overflow-hidden" style="
    background-image: url('/product_images/Construction2.png');
    background-size: cover;
    background-attachment: fixed;
    background-position: center;
    background-repeat: no-repeat;">
    <slot />
    
    <!--End-->

    <!--Footer-->
    <Footer class="mx-auto max-w-screen-lg px-4 pt-16 sm:px-6 sm:pt-24 lg:max-w-screen-2xl lg:px-8"></Footer>
    <!--End-->
    </div>
    <Teleport to="body">
        <ChatPopup/>
    </Teleport>
    
</template>