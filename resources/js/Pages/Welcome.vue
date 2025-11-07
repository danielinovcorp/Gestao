<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    laravelVersion: { type: String, required: true },
    phpVersion: { type: String, required: true },
});

const page = usePage();
const logoLoading = ref(true);
const fallbackTried = ref(false);

// ✅ Nome dinâmico da empresa
const companyName = computed(() => page.props.company?.nome || "Gestão");

// ✅ Logo com fallback melhorado
const logoSrc = computed(() => {
    const companyLogo = page.props.company?.logo_url;
    if (companyLogo) return companyLogo;

    return fallbackTried.value
        ? "/images/logo-gestao-128.png"
        : "/images/logo-gestao-512.png";
});

function onLogoError(e) {
    if (!fallbackTried.value) {
        fallbackTried.value = true;
        setTimeout(() => {
            const img = e.target;
            if (img && img.src !== logoSrc.value) {
                img.src = logoSrc.value;
                logoLoading.value = true;
            }
        }, 0);
    } else {
        // Esconde completamente se ambos falharem
        e.target.style.display = "none";
    }
}

function onLogoLoad() {
    logoLoading.value = false;
}

// ✅ Remove funções não utilizadas do template original
// function handleImageError() {
//     document.getElementById("screenshot-container")?.classList.add("!hidden");
//     document.getElementById("docs-card")?.classList.add("!row-span-1");
//     document.getElementById("docs-card-content")?.classList.add("!flex-row");
//     document.getElementById("background")?.classList.add("!hidden");
// }
</script>

<template>
    <Head :title="companyName">
        <meta
            name="description"
            :content="`${companyName} - Sistema de gestão integrado para clientes, fornecedores, contactos, propostas, encomendas e financeiro`"
        />
    </Head>

    <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
        <!-- Background decorativo -->
        <img
            id="background"
            class="absolute -left-20 top-0 max-w-[877px]"
            src="https://laravel.com/assets/img/welcome/background.svg"
            alt=""
        />

        <div
            class="relative flex min-h-screen flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white"
        >
            <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                <!-- ===== HERO SECTION ===== -->
                <section
                    class="relative mb-10 overflow-hidden rounded-2xl bg-white/80 p-10 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/60 backdrop-blur supports-[backdrop-filter]:bg-white/60 dark:bg-zinc-900/80 dark:ring-zinc-800"
                >
                    <div
                        class="mx-auto flex max-w-4xl flex-col items-center text-center"
                    >
                        <!-- LOGO COM LOADING STATE -->
                        <div
                            data-logo-wrapper
                            class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-white/70 p-2 shadow-lg dark:bg-zinc-800/60 relative"
                        >
                            <img
                                :src="logoSrc"
                                @error="onLogoError"
                                @load="onLogoLoad"
                                :alt="`Logotipo ${companyName}`"
                                class="h-full w-full rounded-xl object-contain transition-opacity duration-300"
                                :class="{ 'opacity-0': logoLoading }"
                                loading="lazy"
                                width="80"
                                height="80"
                            />
                            <!-- Loading spinner -->
                            <div
                                v-if="logoLoading"
                                class="absolute inset-0 flex items-center justify-center"
                            >
                                <div
                                    class="h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-[#FF2D20]"
                                ></div>
                            </div>
                        </div>

                        <!-- TÍTULO DINÂMICO -->
                        <h1
                            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-4xl"
                        >
                            {{ companyName }} — Sistema de Gestão Integrado
                        </h1>

                        <!-- DESCRIÇÃO -->
                        <p
                            class="mt-3 max-w-2xl text-balance text-zinc-600 dark:text-zinc-300"
                        >
                            Clientes, Fornecedores, Contactos, Propostas,
                            Encomendas, OT e Financeiro reunidos num só lugar —
                            simples, rápido e moderno.
                        </p>

                        <!-- BOTÕES DE AÇÃO -->
                        <div
                            class="mt-6 flex flex-wrap items-center justify-center gap-3"
                        >
                            <Link
                                v-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="rounded-lg bg-[#FF2D20] px-5 py-2.5 text-white shadow hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#FF2D20]/70 transition-all duration-200 hover:scale-105"
                            >
                                Ir para o Dashboard
                            </Link>
                            <template v-else>
                                <Link
                                    :href="route('login')"
                                    class="rounded-lg bg-[#FF2D20] px-5 py-2.5 text-white shadow hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#FF2D20]/70 transition-all duration-200 hover:scale-105"
                                >
                                    Entrar no sistema
                                </Link>
                                <Link
                                    v-if="canRegister"
                                    :href="route('register')"
                                    class="rounded-lg px-5 py-2.5 ring-1 ring-zinc-300 hover:bg-zinc-50 dark:ring-zinc-700 dark:hover:bg-zinc-800 transition-all duration-200 hover:scale-105"
                                >
                                    Criar conta
                                </Link>
                            </template>
                        </div>
                    </div>

                    <!-- EFEITO DE FUNDO DECORATIVO -->
                    <div
                        class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-gradient-to-br from-[#FF2D20]/20 to-indigo-500/20 blur-3xl"
                    />
                </section>
                <!-- ===== FIM HERO SECTION ===== -->

                <!-- FOOTER -->
                <footer
                    class="py-16 text-center text-sm text-black dark:text-white/70"
                >
                    Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
                </footer>
            </div>
        </div>
    </div>
</template>
