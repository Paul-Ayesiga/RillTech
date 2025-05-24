<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const name = page.props.name;
const quote = page.props.quote;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <div class="relative hidden h-full flex-col bg-muted p-10 text-white dark:border-r lg:flex">
            <!-- Background with gradient overlay -->
            <div class="absolute inset-0 overflow-hidden">
                <img src="/industry-uses/finance.png" alt="AI technology background" class="w-full h-full object-cover opacity-70">
                <div class="absolute inset-0 bg-gradient-to-r from-gray-900 to-primary/30"></div>
            </div>

            <!-- Logo and brand -->
            <Link :href="route('home')" class="relative z-20 flex items-center text-lg font-medium">
            <img src="/images/logo.png" alt="Rill Tech" class="h-8 w-auto mr-2" />
            <span class="text-xl font-bold">{{ name }}</span>
            </Link>
            !-- Main content -->
            <div class="relative z-20 mt-auto max-w-md">
                <h1 class="text-4xl font-bold mb-8">
                    Create Your <span class='text-primary'>Agent</span> in a blink
                </h1>
                <p class="text-lg text-gray-300 mb-8">Empower your business with intelligent AI agents tailored to your
                    specific needs. Minimal coding required.</p>

                <!-- Features list -->
                <div class="space-y-6 mb-8">

                </div>

                <!-- Social proof -->
                <div class="flex items-center space-x-4">
                    <div class="flex -space-x-2">
                        <div v-for="i in 3" :key="i"
                            class="w-8 h-8 rounded-full border-2 border-white bg-gray-200 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-sm text-gray-300"><span class="font-semibold">2,500+</span> businesses already using
                        our platform</p>
                </div>
            </div>
        </div>
        <div class="lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                <Link :href="route('home')" class="flex flex-col items-center gap-2 font-medium">
                <div class="mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                    <AppLogoIcon class="size-9 fill-current text-[var(--foreground)] dark:text-white" />
                </div>
                <span class="sr-only">{{ title }}</span>
                </Link>
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">{{ title }}</h1>
                    <p class="text-sm text-muted-foreground" v-if="description">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
