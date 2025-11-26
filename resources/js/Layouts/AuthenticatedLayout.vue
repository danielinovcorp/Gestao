<script setup>
import { ref, computed } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import { Link, usePage, router } from "@inertiajs/vue3";

// Ícones do Lucide (Shadcn style)
import { Building2, Plus, ChevronRight, Menu, X } from "lucide-vue-next";

// Componentes Shadcn
import { Button } from "@/Components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";

const showingNavigationDropdown = ref(false);

// NOVO – Sidebar dos tenants
const sidebarOpen = ref(false);
const page = usePage();

const currentTenant = computed(() => page.props.auth?.current_tenant ?? null);
const userTenants = computed(() => page.props.auth?.tenants ?? []);

const hasTenant = computed(() => !!currentTenant.value);

// Trocar de tenant
const switchTenant = async (tenant) => {
    try {
        // Faz a requisição sem preserveState para forçar atualização
        await router.post(route('tenant.switch', tenant.id));
        
        // Recarrega as props atualizadas
        await router.reload({ only: ['auth'] });
        
    } catch (error) {
        console.error('Erro ao trocar de tenant:', error);
    }
};

const createNewTenant = () => {
    router.visit(route("tenants.create"));
    sidebarOpen.value = false;
};

// Função para gerar iniciais (ex: "Minha Empresa Teste" → MT)
const tenantInitials = (tenant) => {
    const words = tenant.name.trim().split(/\s+/);
    if (words.length === 1) {
        return words[0].charAt(0).toUpperCase();
    }
    return (
        words[0].charAt(0) + words[words.length - 1].charAt(0)
    ).toUpperCase();
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <!-- ====================== SIDEBAR DOS TENANTS (VERSÃO FINAL QUE PEDISTE) ====================== -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-20 bg-white border-r border-gray-200 flex flex-col items-center py-6 space-y-4 transition-transform duration-300 lg:translate-x-0 lg:z-auto',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Logo no topo (pequeno) -->
            <Link :href="route('dashboard')" class="mb-8">
                <ApplicationLogo class="h-9 w-9 text-indigo-600" />
            </Link>

            <!-- Botão + para criar nova empresa -->
            <button
                @click="createNewTenant"
                class="w-12 h-12 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center shadow-lg transition-all hover:scale-110"
                title="Criar nova empresa"
            >
                <Plus class="h-6 w-6" />
            </button>

            <!-- Divider sutil -->
            <div class="w-10 h-px bg-gray-300"></div>

            <!-- Lista de tenants (quadrados com iniciais) -->
            <div class="space-y-3 flex-1 overflow-y-auto w-full px-4">
                <button
                    v-for="tenant in userTenants"
                    :key="tenant.id"
                    @click="switchTenant(tenant)"
                    :class="[
                        'w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-md transition-all hover:scale-110',
                        tenant.id === currentTenant?.id
                            ? 'ring-4 ring-indigo-500 ring-offset-2 bg-indigo-600'
                            : 'bg-gray-600 hover:bg-gray-700',
                    ]"
                    :title="tenant.name"
                >
                    <!-- Primeira + última letra (ex: AutoGlass → AG) -->
                    {{ tenantInitials(tenant) }}
                </button>
            </div>

            <!-- Botão fechar mobile (no fundo) -->
            <button @click="sidebarOpen = false" class="lg:hidden mt-auto mb-6">
                <X class="h-6 w-6 text-gray-500" />
            </button>
        </aside>

        <!-- ====================== CONTEÚDO ORIGINAL ====================== -->
        <div class="flex-1 flex flex-col lg:pl-20">
            <!-- Botão mobile para abrir o sidebar -->
            <div class="fixed top-4 left-4 z-40 lg:hidden">
                <Button
                    @click="sidebarOpen = true"
                    variant="outline"
                    size="icon"
                >
                    <Menu class="h-5 w-5" />
                </Button>
            </div>

            <!-- Tudo o que tinhas antes – 100% intacto -->
            <div v-if="hasTenant" class="min-h-screen bg-gray-100">
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

                                <!-- TODOS OS TEUS MENUS – exatamente como estavam -->
                                <div
                                    class="hidden sm:flex sm:ms-10 items-center gap-2"
                                >
                                    <!-- Clientes / Fornecedores -->
                                    <NavLink
                                        :href="
                                            route('entidades.index.clientes')
                                        "
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
                                            route(
                                                'entidades.index.fornecedores',
                                            )
                                        "
                                        :active="
                                            route().current(
                                                'entidades.index.fornecedores',
                                            )
                                        "
                                    >
                                        Fornecedores
                                    </NavLink>
                                    <NavLink
                                        :href="route('contactos.index')"
                                        :active="route().current('contactos.*')"
                                    >
                                        Contactos
                                    </NavLink>
                                    <NavLink
                                        :href="route('propostas.index')"
                                        :active="route().current('propostas.*')"
                                    >
                                        Propostas
                                    </NavLink>

                                    <!-- Encomendas submenu -->
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
                                        >
                                            Encomendas
                                        </button>
                                        <div
                                            class="absolute left-0 top-full z-50"
                                        >
                                            <div
                                                class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition pt-2"
                                            >
                                                <div
                                                    class="min-w-64 rounded-xl border bg-white shadow-lg p-2 flex flex-col gap-1"
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
                                                    >
                                                        Fornecedores
                                                    </NavLink>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Todos os outros menus – exatamente iguais -->
                                    <NavLink
                                        :href="route('docs.index')"
                                        :active="route().current('docs.*')"
                                        >Arquivos</NavLink
                                    >
                                    <NavLink
                                        :href="route('ordens.index')"
                                        :active="route().current('ordens.*')"
                                        >Ordens</NavLink
                                    >

                                    <!-- Gestão de Acessos submenu -->
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
                                        >
                                            Acessos
                                        </button>
                                        <div
                                            class="absolute left-0 top-full z-50"
                                        >
                                            <div
                                                class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition pt-2"
                                            >
                                                <div
                                                    class="min-w-64 rounded-xl border bg-white shadow-lg p-2 flex flex-col gap-1"
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
                                                    >
                                                        Permissões
                                                    </NavLink>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <NavLink
                                        :href="route('calendario.index')"
                                        :active="
                                            route().current('calendario.*')
                                        "
                                        >Calendário</NavLink
                                    >

                                    <!-- Financeiro submenu -->
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
                                        >
                                            Financeiro
                                        </button>
                                        <div
                                            class="absolute left-0 top-full z-50"
                                        >
                                            <div
                                                class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition pt-2"
                                            >
                                                <div
                                                    class="min-w-64 rounded-xl border bg-white shadow-lg p-2 flex flex-col gap-1"
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
                                                    >
                                                        Faturas Fornecedores
                                                    </NavLink>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Configurações submenu – exatamente como tinhas -->
                                    <div class="relative group inline-block">
                                        <button
                                            type="button"
                                            class="px-3 py-2 text-sm font-medium rounded hover:bg-slate-100"
                                            :class="{
                                                'text-indigo-600':
                                                    route().current(
                                                        'config.*',
                                                    ) ||
                                                    route().current(
                                                        'artigos.*',
                                                    ) ||
                                                    route().current('logs.*'),
                                            }"
                                        >
                                            Configurações
                                        </button>
                                        <div
                                            class="absolute left-0 top-full z-50"
                                        >
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
                                                            >Países</NavLink
                                                        >
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
                                                            >Contactos</NavLink
                                                        >
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
                                                            >Calendário</NavLink
                                                        >
                                                    </div>
                                                    <!-- Operacional -->
                                                    <div class="space-y-1">
                                                        <div
                                                            class="px-3 py-1 text-xs uppercase tracking-wide text-slate-500"
                                                        >
                                                            Operacional
                                                        </div>
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
                                                            >Artigos</NavLink
                                                        >
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
                                                            >IVA</NavLink
                                                        >
                                                        <NavLink
                                                            class="block px-3 py-2 rounded hover:bg-slate-50"
                                                            :href="
                                                                route(
                                                                    'logs.index',
                                                                )
                                                            "
                                                            :active="
                                                                route().current(
                                                                    'logs.*',
                                                                )
                                                            "
                                                            >Logs</NavLink
                                                        >
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
                                                            >Empresa</NavLink
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dropdown do utilizador (direita) – exatamente igual -->
                            <div class="hidden sm:ms-6 sm:flex sm:items-center">
                                <div class="relative ms-3">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <span
                                                class="inline-flex rounded-md"
                                            >
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                                >
                                                    {{
                                                        $page.props.auth?.user
                                                            ?.name
                                                    }}
                                                    <svg
                                                        class="-me-0.5 ms-2 h-4 w-4"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
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

                            <!-- Hamburger mobile – exatamente igual -->
                            <div class="-me-2 flex items-center sm:hidden">
                                <button
                                    @click="
                                        showingNavigationDropdown =
                                            !showingNavigationDropdown
                                    "
                                    class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                                >
                                    <svg
                                        class="h-6 w-6"
                                        stroke="currentColor"
                                        fill="none"
                                        viewBox="0 0 24 24"
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

                    <!-- Responsive Navigation Menu – exatamente igual -->
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
                                >Dashboard</ResponsiveNavLink
                            >
                            <ResponsiveNavLink
                                :href="route('entidades.index.clientes')"
                                :active="
                                    route().current('entidades.index.clientes')
                                "
                                >Clientes</ResponsiveNavLink
                            >
                            <ResponsiveNavLink
                                :href="route('entidades.index.fornecedores')"
                                :active="
                                    route().current(
                                        'entidades.index.fornecedores',
                                    )
                                "
                                >Fornecedores</ResponsiveNavLink
                            >
                            <ResponsiveNavLink
                                :href="route('contactos.index')"
                                :active="route().current('contactos.*')"
                                >Contactos</ResponsiveNavLink
                            >
                        </div>

                        <div class="border-t border-gray-200 pb-1 pt-4">
                            <div class="px-4">
                                <div
                                    class="text-base font-medium text-gray-800"
                                >
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

                <!-- Page Heading e Content – exatamente iguais -->
                <header
                    v-if="$slots.header"
                    class="bg-white/90 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-white/80"
                >
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <main>
                    <slot />
                </main>
            </div>

            <!-- SE NÃO TEM TENANT → SÓ MOSTRA A DASHBOARD -->
            <div
                v-else
                class="min-h-screen bg-gray-50 flex items-center justify-center"
            >
                <div class="max-w-4xl mx-auto text-center py-20">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
