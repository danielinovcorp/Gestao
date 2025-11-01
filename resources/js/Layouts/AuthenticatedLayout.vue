<script setup>
import { ref, computed } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import { Link, usePage } from "@inertiajs/vue3";

// const { Toaster, toast } = await import('vue-sonner') // opcional

const showingNavigationDropdown = ref(false);

// Flash messages (se quiser reativar o toaster depois)
/*
const page = usePage();
const flash = computed(() => page.props?.flash ?? {});
watch(flash, (f) => {
  if (!f || typeof f !== 'object') return
  if (f.success) toast.success(String(f.success))
  if (f.error) toast.error(String(f.error))
}, { immediate: true })
*/
</script>

<template>
    <div>
        <!-- <Toaster position="top-right" :rich-colors="true" /> -->

        <div class="min-h-screen bg-gray-100">
            <nav
                class="relative z-40 border-b border-gray-100 bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/80"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link
                                    :href="route('dashboard')"
                                    aria-label="Ir para o Dashboard"
                                >
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <!-- NAV: Menus oficiais do projeto -->
                            <div
                                class="hidden sm:flex sm:ms-10 items-center gap-2"
                            >
                                <!-- ✅ Clientes / Fornecedores -->
                                <NavLink
                                    :href="route('entidades.index.clientes')"
                                    :active="
                                        route().current(
                                            'entidades.index.clientes',
                                        )
                                    "
                                >
                                    Clientes
                                </NavLink>

                                <NavLink
                                    :href="
                                        route('entidades.index.fornecedores')
                                    "
                                    :active="
                                        route().current(
                                            'entidades.index.fornecedores',
                                        )
                                    "
                                >
                                    Fornecedores
                                </NavLink>

                                <!-- ✅ Contactos -->
                                <NavLink
                                    :href="route('contactos.index')"
                                    :active="route().current('contactos.*')"
                                >
                                    Contactos
                                </NavLink>

                                <!-- ⚙️ Propostas -->
                                <NavLink
                                    :href="route('propostas.index')"
                                    :active="route().current('propostas.*')"
                                >
                                    Propostas
                                </NavLink>

                                <!-- ✅ Encomendas (submenu) -->
                                <div class="relative group inline-block">
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-150 ease-in-out border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 focus:outline-none"
                                        :class="{
                                            'border-indigo-500 text-gray-900':
                                                route().current(
                                                    'encomendas.clientes.*',
                                                ) ||
                                                route().current(
                                                    'encomendas.fornecedores.*',
                                                ),
                                        }"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                    >
                                        Encomendas
                                    </button>

                                    <!-- wrapper absoluto colado ao botão -->
                                    <div class="absolute left-0 top-full z-50">
                                        <!-- a 'ponte' é este pt-2 (área “vazia” ainda hoverable) -->
                                        <div
                                            class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition pt-2"
                                        >
                                            <div
                                                class="min-w-64 rounded-xl border bg-white shadow-lg p-2 flex flex-col gap-1"
                                                role="menu"
                                            >
                                                <NavLink
                                                    class="block px-3 py-2 rounded hover:bg-slate-50"
                                                    :href="
                                                        route(
                                                            'encomendas.clientes.index',
                                                        )
                                                    "
                                                    :active="
                                                        route().current(
                                                            'encomendas.clientes.*',
                                                        )
                                                    "
                                                    role="menuitem"
                                                >
                                                    Clientes
                                                </NavLink>
                                                <NavLink
                                                    class="block px-3 py-2 rounded hover:bg-slate-50"
                                                    :href="
                                                        route(
                                                            'encomendas.fornecedores.index',
                                                        )
                                                    "
                                                    :active="
                                                        route().current(
                                                            'encomendas.fornecedores.*',
                                                        )
                                                    "
                                                    role="menuitem"
                                                >
                                                    Fornecedores
                                                </NavLink>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ⚙️ Arquivo Digital -->
                                <NavLink
                                    :href="route('docs.index')"
                                    :active="route().current('docs.*')"
                                >
                                    Arquivos
                                </NavLink>

                                <!-- ⚙️ Ordens de Trabalho -->
                                <NavLink
                                    :href="route('ordens.index')"
                                    :active="route().current('ordens.*')"
                                >
                                    Ordens
                                </NavLink>

                                <!-- ⚙️ Gestão de Acessos (submenu) -->
                                <div class="relative group inline-block">
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-150 ease-in-out border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 focus:outline-none"
                                        :class="{
                                            'border-indigo-500 text-gray-900':
                                                route().current(
                                                    'access.users.*',
                                                ) ||
                                                route().current(
                                                    'access.roles.*',
                                                ),
                                        }"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                    >
                                        Acessos
                                    </button>

                                    <div class="absolute left-0 top-full z-50">
                                        <div
                                            class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition pt-2"
                                        >
                                            <div
                                                class="min-w-64 rounded-xl border bg-white shadow-lg p-2 flex flex-col gap-1"
                                                role="menu"
                                            >
                                                <NavLink
                                                    class="inline-flex items-center px-3 py-2 rounded hover:bg-slate-50"
                                                    :href="
                                                        route(
                                                            'access.users.index',
                                                        )
                                                    "
                                                    :active="
                                                        route().current(
                                                            'access.users.*',
                                                        )
                                                    "
                                                    role="menuitem"
                                                >
                                                    Utilizadores
                                                </NavLink>

                                                <NavLink
                                                    class="inline-flex items-center px-3 py-2 rounded hover:bg-slate-50"
                                                    :href="
                                                        route(
                                                            'access.roles.index',
                                                        )
                                                    "
                                                    :active="
                                                        route().current(
                                                            'access.roles.*',
                                                        )
                                                    "
                                                    role="menuitem"
                                                >
                                                    Permissões
                                                </NavLink>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ⚙️ Calendário -->
                                <NavLink
                                    :href="route('calendario.index')"
                                    :active="route().current('calendario.*')"
                                >
                                    Calendário
                                </NavLink>

                                <!-- 💰 Financeiro (submenu) -->
                                <div class="relative group inline-block">
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-150 ease-in-out border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 focus:outline-none"
                                        :class="{
                                            'border-indigo-500 text-gray-900':
                                                route().current(
                                                    'financeiro.contas-bancarias',
                                                ) ||
                                                route().current(
                                                    'financeiro.conta-corrente-clientes',
                                                ) ||
                                                route().current(
                                                    'financeiro.faturas-fornecedor.*',
                                                ),
                                        }"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                    >
                                        Financeiro
                                    </button>

                                    <div class="absolute left-0 top-full z-50">
                                        <div
                                            class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition pt-2"
                                        >
                                            <div
                                                class="min-w-64 rounded-xl border bg-white shadow-lg p-2 flex flex-col gap-1"
                                                role="menu"
                                            >
                                                <NavLink
                                                    class="inline-flex items-center px-3 py-2 rounded hover:bg-slate-50"
                                                    :href="
                                                        route(
                                                            'financeiro.contas-bancarias',
                                                        )
                                                    "
                                                    :active="
                                                        route().current(
                                                            'financeiro.contas-bancarias',
                                                        )
                                                    "
                                                    role="menuitem"
                                                >
                                                    Contas Bancárias
                                                </NavLink>

                                                <NavLink
                                                    class="inline-flex items-center px-3 py-2 rounded hover:bg-slate-50"
                                                    :href="
                                                        route(
                                                            'financeiro.conta-corrente-clientes',
                                                        )
                                                    "
                                                    :active="
                                                        route().current(
                                                            'financeiro.conta-corrente-clientes',
                                                        )
                                                    "
                                                    role="menuitem"
                                                >
                                                    Conta Corrente Clientes
                                                </NavLink>

                                                <NavLink
                                                    class="inline-flex items-center px-3 py-2 rounded hover:bg-slate-50"
                                                    :href="
                                                        route(
                                                            'financeiro.faturas-fornecedor.index',
                                                        )
                                                    "
                                                    :active="
                                                        route().current(
                                                            'financeiro.faturas-fornecedor.*',
                                                        )
                                                    "
                                                    role="menuitem"
                                                >
                                                    Faturas Fornecedores
                                                </NavLink>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ⚙️ Configurações (submenu) — atualizado -->
                                <div class="relative group inline-block">
                                    <button
                                        type="button"
                                        class="px-3 py-2 text-sm font-medium rounded hover:bg-slate-100"
                                        :class="{
                                            'text-indigo-600':
                                                route().current('config.*') ||
                                                route().current('artigos.*') ||
                                                route().current('logs.*'),
                                        }"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                    >
                                        Configurações
                                    </button>

                                    <!-- dropdown: colado ao botão, subitens em coluna -->
                                    <div class="absolute left-0 top-full z-50">
                                        <!-- 'ponte' hoverable -->
                                        <div class="pt-2">
                                            <div
                                                class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition min-w-[22rem] rounded-xl border bg-white shadow-lg p-3 grid grid-cols-1 gap-1"
                                            >
                                                <!-- Tabelas de Base -->
                                                <div class="space-y-1">
                                                    <div
                                                        class="px-3 py-1 text-xs uppercase tracking-wide text-slate-500"
                                                    >
                                                        Tabelas de Base
                                                    </div>

                                                    <!-- Países -->
                                                    <NavLink
                                                        class="block px-3 py-2 rounded hover:bg-slate-50"
                                                        :href="
                                                            route(
                                                                'config.paises.index',
                                                            )
                                                        "
                                                        :active="
                                                            route().current(
                                                                'config.paises.*',
                                                            )
                                                        "
                                                    >
                                                        Países
                                                    </NavLink>

                                                    <!-- Contactos (Funções) -->
                                                    <NavLink
                                                        class="block px-3 py-2 rounded hover:bg-slate-50"
                                                        :href="
                                                            route(
                                                                'config.funcoes-contacto.index',
                                                            )
                                                        "
                                                        :active="
                                                            route().current(
                                                                'config.funcoes-contacto.*',
                                                            )
                                                        "
                                                    >
                                                        Contactos
                                                    </NavLink>

                                                    <!-- Calendário (default: Tipos) -->
                                                    <NavLink
                                                        class="block px-3 py-2 rounded hover:bg-slate-50"
                                                        :href="
                                                            route(
                                                                'config.calendario.tipos.index',
                                                            )
                                                        "
                                                        :active="
                                                            route().current(
                                                                'config.calendario.tipos.*',
                                                            ) ||
                                                            route().current(
                                                                'config.calendario.acoes.*',
                                                            )
                                                        "
                                                    >
                                                        Calendário
                                                    </NavLink>
                                                </div>

                                                <!-- Operacional -->
                                                <div class="space-y-1">
                                                    <div
                                                        class="px-3 py-1 text-xs uppercase tracking-wide text-slate-500"
                                                    >
                                                        Operacional
                                                    </div>

                                                    <!-- Artigos -->
                                                    <NavLink
                                                        class="block px-3 py-2 rounded hover:bg-slate-50"
                                                        :href="
                                                            route(
                                                                'config.artigos.index',
                                                            )
                                                        "
                                                        :active="
                                                            route().current(
                                                                'config.artigos.*',
                                                            )
                                                        "
                                                    >
                                                        Artigos
                                                    </NavLink>

                                                    <!-- IVA -->
                                                    <NavLink
                                                        class="block px-3 py-2 rounded hover:bg-slate-50"
                                                        :href="
                                                            route(
                                                                'config.iva.index',
                                                            )
                                                        "
                                                        :active="
                                                            route().current(
                                                                'config.iva.*',
                                                            )
                                                        "
                                                    >
                                                        IVA
                                                    </NavLink>

                                                    <!-- Logs -->
                                                    <NavLink
                                                        class="block px-3 py-2 rounded hover:bg-slate-50"
                                                        :href="
                                                            route('logs.index')
                                                        "
                                                        :active="
                                                            route().current(
                                                                'logs.*',
                                                            )
                                                        "
                                                    >
                                                        Logs
                                                    </NavLink>

                                                    <!-- Empresa -->
                                                    <NavLink
                                                        class="block px-3 py-2 rounded hover:bg-slate-50"
                                                        :href="
                                                            route(
                                                                'config.empresa',
                                                            )
                                                        "
                                                        :active="
                                                            route().current(
                                                                'config.empresa',
                                                            )
                                                        "
                                                    >
                                                        Empresa
                                                    </NavLink>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {{
                                                    $page.props.auth?.user?.name
                                                }}
                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                            >Profile</DropdownLink
                                        >
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                            >Log Out</DropdownLink
                                        >
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                                :aria-expanded="
                                    showingNavigationDropdown ? 'true' : 'false'
                                "
                                aria-label="Abrir menu"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Dashboard
                        </ResponsiveNavLink>

                        <!-- Mobile: mantém só os prontos -->
                        <ResponsiveNavLink
                            :href="route('entidades.index.clientes')"
                            :active="
                                route().current('entidades.index.clientes')
                            "
                        >
                            Clientes
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            :href="route('entidades.index.fornecedores')"
                            :active="
                                route().current('entidades.index.fornecedores')
                            "
                        >
                            Fornecedores
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            :href="route('contactos.index')"
                            :active="route().current('contactos.*')"
                        >
                            Contactos
                        </ResponsiveNavLink>

                        <!-- Demais itens mobile comentados até concluirmos
            <ResponsiveNavLink :href="route('propostas.index')" :active="route().current('propostas.*')">
              Propostas
            </ResponsiveNavLink>
            <ResponsiveNavLink :href="route('calendario.index')" :active="route().current('calendario.*')">
              Calendário
            </ResponsiveNavLink>
            <ResponsiveNavLink :href="route('encomendas.clientes.index')" :active="route().current('encomendas.clientes.*')">
              Encomendas - Clientes
            </ResponsiveNavLink>
            <ResponsiveNavLink :href="route('encomendas.fornecedores.index')" :active="route().current('encomendas.fornecedores.*')">
              Encomendas - Fornecedores
            </ResponsiveNavLink>
            <ResponsiveNavLink :href="route('ot.index')" :active="route().current('ot.*')">
              Ordens de Trabalho
            </ResponsiveNavLink>
            <ResponsiveNavLink :href="route('arquivo.index')" :active="route().current('arquivo.*')">
              Arquivo Digital
            </ResponsiveNavLink>
            <ResponsiveNavLink :href="route('config.paises.index')" :active="route().current('config.*')">
              Configurações
            </ResponsiveNavLink>
            -->
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="border-t border-gray-200 pb-1 pt-4">
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800">
                                {{ $page.props.auth?.user?.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth?.user?.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"
                                >Profile</ResponsiveNavLink
                            >
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                                >Log Out</ResponsiveNavLink
                            >
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                v-if="$slots.header"
                class="bg-white/90 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-white/80"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
