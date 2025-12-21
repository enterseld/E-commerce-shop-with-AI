<script setup>
import { nextTick, ref, watch } from 'vue'
import axios from 'axios'
import { pipeline, env } from '@xenova/transformers'

const isOpen = ref(false)
const session = ref('')
const sessionType = ref('normal')
const inputText = ref('')
const embedding = ref([])
const loading = ref(false)
const response = ref(null)
const reply = ref('')
const chatMessages = ref([])
const messagesConsult = ref([])
const messagesEnd = ref(null)

// Resizable chat states
const chatWidth = ref(320)
const chatHeight = ref(480)
const isResizing = ref(false)
const resizeStartX = ref(0)
const resizeStartY = ref(0)
const resizeStartWidth = ref(0)
const resizeStartHeight = ref(0)

env.allowRemoteModels = true
env.allowLocalModels = false

const initSession = async (openToggle = true) => {
    try {
        const response = await axios.post('/initSession', {
            session_type: sessionType.value
        })
        console.log('Session started:', response.data)
        session.value = response.data.session

        chatMessages.value = response.data.messages.map((msg, index) => ({
            content: msg.message,
            role: index % 2 === 0 ? 'user' : 'assistant'
        }))

        console.log("messages array:", chatMessages.value)
        if (!response.data.messages || response.data.messages.length === 0 && sessionType === 'product') {
            chatMessages.value.push({
                role: 'system',
                content: `Ти — україномовний консультант магазину алмазного інструменту. Відповідай чітко, ввічливо та лише українською. Якщо користувач запитує про товар — допоможи обрати найкращий варіант з наданих, але обирай тільки товари, які надані, не вигадуй інші.`,
            })
        }
        else if (!response.data.messages || response.data.messages.length === 0 && sessionType === 'normal') {
            chatMessages.value.push({
                role: 'system',
                content: `Ти — україномовний консультант магазину алмазного інструменту. Відповідай чітко, ввічливо та лише українською. Якщо запит про доставку, оплату, повернення — відповідай відповідно до політики магазину, якщо ні - скажи, що не маєш відповіді за цими темами.`,
            })
        }
    } catch (error) {
        console.error('Failed to start session:', error)
    }

    if (openToggle) {
        toggleChat()
    }
}

const toggleSessionType = async () => {
    sessionType.value = sessionType.value === 'normal' ? 'product' : 'normal'
    initSession(false)
}

const productQuestion = async () => {
    loading.value = true
    try {
        if (!response.value) {
            const extractor = await pipeline('feature-extraction', 'Xenova/all-MiniLM-L6-v2')
            const result = await extractor(inputText.value, { pooling: 'mean', normalize: true })
            const vector = result.data
            console.log(vector)

            response.value = await axios.post('/search', { vector })

            const matches = response.value.data.matches || []

            const productList = matches.map((match, i) =>
                `${i + 1}. ${match.metadata.text}`
            ).join('\n')

            messagesConsult.value.push({
                role: 'user',
                content: `Ось мій запит:${inputText.value}. Ось список релевантних товарів:\n${productList} Який краще обрати(обери тільки один)? Розкажи про цей товар відповідно до запитання. Не кажи нічого про список, тільки обери із нього товар.`,
            })
        } else {
            const extractor = await pipeline('feature-extraction', 'Xenova/all-MiniLM-L6-v2')
            const result = await extractor(inputText.value, { pooling: 'mean', normalize: true })
            const vector = result.data
            console.log(vector)

            response.value = await axios.post('/search', { vector })

            const matches = response.value.data.matches || []

            const productList = matches.map((match, i) =>
                `${i + 1}. ${match.metadata.text}`
            ).join('\n')

            messagesConsult.value.push({
                role: 'user',
                content: `Ось наступний запит:${inputText.value}. Ось список релевантних товарів:\n${productList}, якщо потрібно, якщо ні - прост дай відповідь відповідно до попередніх запитів.`,
            })
        }
        chatMessages.value.push({ role: 'user', content: inputText.value })
        console.log(messagesConsult.value)

        const res = await axios.post('/ask', {
            messages: messagesConsult.value,
            session_id: session.value.id,
            last_message: inputText.value
        })
        console.log(res)
        const assistantReply = res.data.reply
        console.log('assistantReply:', assistantReply)
        reply.value = assistantReply
        chatMessages.value.push({ role: 'assistant', content: assistantReply })

    } catch (error) {
        console.error('Помилка embedding, пошуку або відповіді:', error)
        reply.value = 'Вибач, щось пішло не так. Спробуй ще раз.'
    } finally {
        loading.value = false
        inputText.value = ''
    }
}

const normalQuestion = async () => {
    loading.value = true
    try {
        if (!response.value) {
            const extractor = await pipeline('feature-extraction', 'Xenova/all-MiniLM-L6-v2')
            const result = await extractor(inputText.value, { pooling: 'mean', normalize: true })
            const vector = result.data
            console.log(vector)

            response.value = await axios.post('/search/tos', { vector })

            const matches = response.value.data.matches || []

            const productList = matches.map((match, i) =>
                `${i + 1}. ${match.metadata.text}`
            ).join('\n')

            messagesConsult.value.push({
                role: 'user',
                content: `Ось мій запит:${inputText.value}. Ось дані із Terms of Service:\n${productList} Дай відповідь на запит? Поясни, чому так.`,
            })
        } else {
            messagesConsult.value.push({
                role: 'user',
                content: `Ось наступний запит:${inputText.value}.`,
            })
        }
        chatMessages.value.push({ role: 'user', content: inputText.value })
        console.log(messagesConsult.value)

        const res = await axios.post('/ask', {
            messages: messagesConsult.value,
            session_id: session.value.id,
            last_message: inputText.value
        })
        console.log(res)
        const assistantReply = res.data.reply
        console.log('assistantReply:', assistantReply)
        reply.value = assistantReply
        chatMessages.value.push({ role: 'assistant', content: assistantReply })

    } catch (error) {
        console.error('Помилка embedding, пошуку або відповіді:', error)
        reply.value = 'Вибач, щось пішло не так. Спробуй ще раз.'
    } finally {
        loading.value = false
        inputText.value = ''
    }
}

const formatMessage = (text) => {
    if (!text) return ''

    text = text.replace(/###\s+(.+?)(\n|$)/g, '<h3 class="text-base font-bold mt-3 mb-2">$1</h3>')
    text = text.replace(/\*\*(.+?)\*\*/g, '<strong class="font-semibold">$1</strong>')
    text = text.replace(/\n/g, '<br>')

    return text
}

// Resize functions
const startResize = (e, direction) => {
    isResizing.value = true
    resizeStartX.value = e.clientX
    resizeStartY.value = e.clientY
    resizeStartWidth.value = chatWidth.value
    resizeStartHeight.value = chatHeight.value
    
    const onMouseMove = (moveEvent) => {
        if (!isResizing.value) return
        
        if (direction.includes('left')) {
            const deltaX = resizeStartX.value - moveEvent.clientX
            chatWidth.value = Math.max(280, Math.min(600, resizeStartWidth.value + deltaX))
        }
        if (direction.includes('top')) {
            const deltaY = resizeStartY.value - moveEvent.clientY
            chatHeight.value = Math.max(300, Math.min(800, resizeStartHeight.value + deltaY))
        }
    }
    
    const onMouseUp = () => {
        isResizing.value = false
        document.removeEventListener('mousemove', onMouseMove)
        document.removeEventListener('mouseup', onMouseUp)
    }
    
    document.addEventListener('mousemove', onMouseMove)
    document.addEventListener('mouseup', onMouseUp)
}

watch(chatMessages, () => {
    nextTick(() => {
        messagesEnd.value?.scrollIntoView({ behavior: 'smooth' })
    })
})

function toggleChat() {
    isOpen.value = !isOpen.value
}
</script>

<template>
    <!-- Chat Toggle Button -->
    <transition name="fade">
        <button v-if="!isOpen" @click="initSession"
            class="fixed bottom-6 right-6 z-50 bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition duration-300 ease-in-out">
            💬
        </button>
    </transition>

    <!-- Chat Box -->
    <div v-if="isOpen"
        :style="{ width: chatWidth + 'px', height: chatHeight + 'px' }"
        class="fixed z-50 bottom-20 right-4 bg-white border border-gray-300 rounded-xl shadow-xl flex flex-col">

        <!-- Resize handles -->
        <div @mousedown="startResize($event, 'left-top')"
            class="absolute -left-1 -top-1 w-3 h-3 bg-blue-500 rounded-full cursor-nwse-resize hover:bg-blue-600 z-10"></div>
        <div @mousedown="startResize($event, 'top')"
            class="absolute left-1/2 -translate-x-1/2 -top-1 w-8 h-2 bg-blue-500 rounded cursor-ns-resize hover:bg-blue-600 z-10"></div>
        <div @mousedown="startResize($event, 'left')"
            class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-8 bg-blue-500 rounded cursor-ew-resize hover:bg-blue-600 z-10"></div>

        <!-- Header -->
        <div class="bg-blue-600 text-white px-4 py-2 rounded-t-xl flex justify-between items-center flex-shrink-0">
            <h3 class="font-semibold">Chat</h3>
            <div class="flex items-center space-x-2">
                <button @click="toggleSessionType"
                    class="bg-white text-blue-600 px-2 py-1 rounded text-xs hover:bg-gray-100">
                    {{ sessionType === 'normal' ? 'Умови обслуговування' : 'Підбір товару' }}
                </button>
                <button @click="toggleChat" class="text-white hover:text-gray-200">✖</button>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="p-4 overflow-y-auto text-sm text-gray-700 flex flex-col justify-start flex-1">
            <p v-if="sessionType === 'normal'" class="mb-2">👋 Вітаю! Чим можу допомогти?</p>
            <p v-if="sessionType === 'product'" class="mb-2">👋 Вітаю! Можу допомогти підібрати товар за потреби!</p>

            <div v-for="(msg, index) in chatMessages" :key="index" class="mb-2">
                <!-- USER message -->
                <div v-if="msg.role === 'user'" class="flex justify-end">
                    <div
                        class="bg-blue-600 text-white px-4 py-2 max-w-[75%] rounded-2xl rounded-br-none text-sm shadow-md">
                        {{ msg.content }}
                    </div>
                </div>

                <!-- ASSISTANT message -->
                <div v-else class="flex justify-start">
                    <div
                        class="bg-gray-100 text-gray-900 w-full px-4 py-3 rounded-2xl rounded-bl-none text-sm shadow-sm">
                        <div v-html="formatMessage(msg.content)"></div>
                    </div>
                </div>
            </div>

            <div ref="messagesEnd" />
        </div>

        <!-- Input Area -->
        <div v-if="sessionType === 'product'" class="p-2 border-t border-gray-200 flex flex-shrink-0">
            <input v-model="inputText" @keyup.enter="productQuestion" type="text" placeholder="Напишіть ваше питання..."
                class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none text-sm" />
            <button @click="productQuestion" :disabled="loading || !inputText"
                class="bg-blue-600 text-white px-4 py-2 rounded-r-md hover:bg-blue-700 text-sm flex items-center justify-center min-w-[90px]">
                <svg v-if="loading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>
                <span v-else>Надіслати</span>
            </button>
        </div>

        <div v-if="sessionType === 'normal'" class="p-2 border-t border-gray-200 flex flex-shrink-0">
            <input v-model="inputText" @keyup.enter="normalQuestion" type="text" placeholder="Напишіть ваше питання..."
                class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none text-sm" />
            <button @click="normalQuestion" :disabled="loading || !inputText"
                class="bg-blue-600 text-white px-4 py-2 rounded-r-md hover:bg-blue-700 text-sm flex items-center justify-center min-w-[90px]">
                <svg v-if="loading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>
                <span v-else>Надіслати</span>
            </button>
        </div>
    </div>
</template>