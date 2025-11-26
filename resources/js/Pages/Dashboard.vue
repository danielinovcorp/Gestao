<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";
import {
    Plus,
    Building2,
    Users,
    Calendar,
    Check,
    XCircle,
} from "lucide-vue-next";

const page = usePage();
const user = computed(() => page.props.auth.user);
const tenants = computed(() => page.props.auth.tenants || []);
const currentTenant = computed(() => page.props.auth.current_tenant);

// ==== PLANOS FAKE (100% VISUAL) ====
const selectedPlan = ref("trial"); // começa sempre no trial
const showToast = ref(false);

const selectPlan = (plan) => {
    selectedPlan.value = plan;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 3000);
};

const switchTenant = async (tenant) => {
    if (tenant.id === currentTenant.value?.id) return;
    await router.post(route("tenant.switch", tenant.id));
    await router.reload({ only: ["auth"] });
};

const createTenant = () => {
    router.visit(route("tenants.create"));
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-8">
                        <!-- Título -->
                        <div class="text-center mb-12">
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                                Olá, {{ user?.name }}!
                            </h1>
                            <p class="text-lg text-gray-600">
                                {{
                                    tenants.length === 0
                                        ? "Crie a sua primeira empresa para começar"
                                        : `Você tem ${tenants.length} empresa${tenants.length > 1 ? "s" : ""}`
                                }}
                            </p>
                        </div>

                        <!-- Sem tenants -->
                        <div
                            v-if="tenants.length === 0"
                            class="text-center py-16"
                        >
                            <div
                                class="bg-gray-100 border-2 border-dashed rounded-xl w-32 h-32 mx-auto mb-6 flex items-center justify-center"
                            >
                                <Building2 class="h-16 w-16 text-gray-400" />
                            </div>
                            <h3
                                class="text-2xl font-semibold text-gray-800 mb-4"
                            >
                                Nenhuma empresa criada
                            </h3>
                            <Button
                                size="lg"
                                @click="createTenant"
                                class="gap-3"
                            >
                                <Plus class="h-5 w-5" />
                                Criar a minha primeira empresa
                            </Button>
                        </div>

                        <!-- Com tenants -->
                        <div
                            v-else
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                        >
                            <Card
                                v-for="tenant in tenants"
                                :key="tenant.id"
                                :class="{
                                    'ring-4 ring-indigo-500 ring-offset-4 shadow-xl':
                                        tenant.id === currentTenant?.id,
                                    'cursor-pointer hover:shadow-lg transition-all':
                                        tenant.id !== currentTenant?.id,
                                }"
                                @click="
                                    tenant.id !== currentTenant?.id &&
                                    switchTenant(tenant)
                                "
                            >
                                <CardHeader>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div
                                            class="bg-indigo-100 rounded-lg p-3"
                                        >
                                            <Building2
                                                class="h-8 w-8 text-indigo-600"
                                            />
                                        </div>
                                        <span
                                            v-if="
                                                tenant.id === currentTenant?.id
                                            "
                                            class="bg-indigo-100 text-indigo-800 text-xs font-medium px-3 py-1 rounded-full"
                                        >
                                            Atual
                                        </span>
                                    </div>
                                    <CardTitle class="mt-4 text-xl">{{
                                        tenant.name
                                    }}</CardTitle>
                                    <CardDescription>
                                        {{
                                            tenant.pivot?.role === "owner"
                                                ? "Proprietário"
                                                : "Membro"
                                        }}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div
                                        class="text-sm text-gray-600 space-y-2"
                                    >
                                        <div class="flex items-center gap-2">
                                            <Users class="h-4 w-4" />
                                            <span>1 usuário</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Calendar class="h-4 w-4" />
                                            <span
                                                >Criado em
                                                {{
                                                    new Date(
                                                        tenant.created_at,
                                                    ).toLocaleDateString(
                                                        "pt-PT",
                                                    )
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Botão + -->
                            <Card
                                class="border-2 border-dashed border-gray-300 hover:border-gray-400 cursor-pointer transition-all"
                                @click="createTenant"
                            >
                                <CardContent
                                    class="flex flex-col items-center justify-center h-full py-16"
                                >
                                    <div
                                        class="bg-gray-200 rounded-full p-6 mb-4"
                                    >
                                        <Plus class="h-12 w-12 text-gray-500" />
                                    </div>
                                    <p
                                        class="text-lg font-medium text-gray-700"
                                    >
                                        Criar nova empresa
                                    </p>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- ====================== SEÇÃO DE PLANOS (100% FAKE) ====================== -->
                        <div class="mt-20">
                            <h2
                                class="text-2xl font-bold text-center mb-12 text-gray-800"
                            >
                                Escolha o seu plano
                            </h2>

                            <div
                                class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto"
                            >
                                <!-- TRIAL -->
                                <div class="relative">
                                    <Card
                                        :class="
                                            selectedPlan === 'trial'
                                                ? 'ring-4 ring-indigo-500 shadow-2xl'
                                                : ''
                                        "
                                    >
                                        <CardHeader class="text-center pb-8">
                                            <div
                                                class="absolute -top-4 left-1/2 -translate-x-1/2"
                                            >
                                                <span
                                                    v-if="
                                                        selectedPlan === 'trial'
                                                    "
                                                    class="bg-indigo-600 text-white px-6 py-2 rounded-full text-sm font-bold"
                                                >
                                                    Plano Atual
                                                </span>
                                                <span
                                                    v-else
                                                    class="bg-gray-200 text-gray-600 px-6 py-2 rounded-full text-sm font-bold"
                                                >
                                                    15 dias restantes
                                                </span>
                                            </div>
                                            <CardTitle class="text-3xl mt-4"
                                                >Trial</CardTitle
                                            >
                                            <div class="mt-6">
                                                <span class="text-5xl font-bold"
                                                    >€0</span
                                                >
                                                <span class="text-gray-500"
                                                    >/mês</span
                                                >
                                            </div>
                                        </CardHeader>
                                        <CardContent class="space-y-5 text-sm">
                                            <ul class="space-y-3">
                                                <li
                                                    class="flex items-center gap-3"
                                                >
                                                    <Check
                                                        class="h-5 w-5 text-green-600"
                                                    />
                                                    1 utilizador
                                                </li>
                                                <li
                                                    class="flex items-center gap-3"
                                                >
                                                    <Check
                                                        class="h-5 w-5 text-green-600"
                                                    />
                                                    5GB armazenamento
                                                </li>
                                                <li
                                                    class="flex items-center gap-3"
                                                >
                                                    <Check
                                                        class="h-5 w-5 text-green-600"
                                                    />
                                                    Suporte por email
                                                </li>
                                                <li
                                                    class="flex items-center gap-3 text-gray-400"
                                                >
                                                    <XCircle class="h-5 w-5" />
                                                    Relatórios avançados
                                                </li>
                                            </ul>
                                            <Button
                                                class="w-full mt-6"
                                                variant="outline"
                                                :disabled="
                                                    selectedPlan === 'trial'
                                                "
                                                @click="selectPlan('trial')"
                                            >
                                                {{
                                                    selectedPlan === "trial"
                                                        ? "Plano Atual"
                                                        : "Continuar no Trial"
                                                }}
                                            </Button>
                                        </CardContent>
                                    </Card>
                                </div>

                                <!-- STANDARD -->
                                <div class="relative">
                                    <Card
                                        :class="
                                            selectedPlan === 'standard'
                                                ? 'ring-4 ring-indigo-500 shadow-2xl transform scale-105'
                                                : ''
                                        "
                                    >
                                        <div
                                            class="absolute -top-5 left-1/2 -translate-x-1/2 bg-orange-500 text-white px-6 py-2 rounded-full text-sm font-bold"
                                        >
                                            Recomendado
                                        </div>
                                        <CardHeader
                                            class="text-center pb-8 pt-6"
                                        >
                                            <CardTitle class="text-3xl"
                                                >Standard</CardTitle
                                            >
                                            <div class="mt-6">
                                                <span class="text-5xl font-bold"
                                                    >€29</span
                                                >
                                                <span class="text-gray-500"
                                                    >/mês</span
                                                >
                                            </div>
                                        </CardHeader>
                                        <CardContent class="space-y-5 text-sm">
                                            <ul class="space-y-3">
                                                <li
                                                    class="flex items-center gap-3"
                                                >
                                                    <Check
                                                        class="h-5 w-5 text-green-600"
                                                    />
                                                    10 utilizadores
                                                </li>
                                                <li
                                                    class="flex items-center gap-3"
                                                >
                                                    <Check
                                                        class="h-5 w-5 text-green-600"
                                                    />
                                                    100GB armazenamento
                                                </li>
                                                <li
                                                    class="flex items-center gap-3"
                                                >
                                                    <Check
                                                        class="h-5 w-5 text-green-600"
                                                    />
                                                    Suporte prioritário
                                                </li>
                                                <li
                                                    class="flex items-center gap-3"
                                                >
                                                    <Check
                                                        class="h-5 w-5 text-green-600"
                                                    />
                                                    Relatórios avançados
                                                </li>
                                                <li
                                                    class="flex items-center gap-3"
                                                >
                                                    <Check
                                                        class="h-5 w-5 text-green-600"
                                                    />
                                                    Integrações
                                                </li>
                                            </ul>
                                            <Button
                                                class="w-full mt-6"
                                                :variant="
                                                    selectedPlan === 'standard'
                                                        ? 'default'
                                                        : 'outline'
                                                "
                                                @click="selectPlan('standard')"
                                            >
                                                {{
                                                    selectedPlan === "standard"
                                                        ? "Plano Atual"
                                                        : "Ativar Standard"
                                                }}
                                            </Button>
                                        </CardContent>
                                    </Card>
                                </div>

                                <!-- PREMIUM -->
                                <div class="relative">
                                    <Card
                                        :class="
                                            selectedPlan === 'premium'
                                                ? 'ring-4 ring-indigo-500 shadow-2xl'
                                                : ''
                                        "
                                    >
                                        <CardHeader class="text-center pb-8">
                                            <CardTitle class="text-3xl"
                                                >Premium</CardTitle
                                            >
                                            <div class="mt-6">
                                                <span class="text-5xl font-bold"
                                                    >€99</span
                                                >
                                                <span class="text-gray-500"
                                                    >/mês</span
                                                >
                                            </div>
                                        </CardHeader>
                                        <CardContent class="space-y-5 text-sm">
                                            <ul class="space-y-3">
                                                <li
                                                    class="flex items-center gap-3"
                                                >
                                                    <Check
                                                        class="h-5 w-5 text-green-600"
                                                    />
                                                    Utilizadores ilimitados
                                                </li>
                                                <li
                                                    class="flex items-center gap-3"
                                                >
                                                    <Check
                                                        class="h-5 w-5 text-green-600"
                                                    />
                                                    Armazenamento ilimitado
                                                </li>
                                                <li
                                                    class="flex items-center gap-3"
                                                >
                                                    <Check
                                                        class="h-5 w-5 text-green-600"
                                                    />
                                                    Suporte 24/7
                                                </li>
                                                <li
                                                    class="flex items-center gap-3"
                                                >
                                                    <Check
                                                        class="h-5 w-5 text-green-600"
                                                    />
                                                    Gestor dedicado
                                                </li>
                                                <li
                                                    class="flex items-center gap-3"
                                                >
                                                    <Check
                                                        class="h-5 w-5 text-green-600"
                                                    />
                                                    Tudo do Standard
                                                </li>
                                            </ul>
                                            <Button
                                                class="w-full mt-6"
                                                :variant="
                                                    selectedPlan === 'premium'
                                                        ? 'default'
                                                        : 'outline'
                                                "
                                                @click="selectPlan('premium')"
                                            >
                                                {{
                                                    selectedPlan === "premium"
                                                        ? "Plano Atual"
                                                        : "Ativar Premium"
                                                }}
                                            </Button>
                                        </CardContent>
                                    </Card>
                                </div>
                            </div>

                            <!-- Toast fake -->
                            <div
                                v-if="showToast"
                                class="fixed bottom-8 left-1/2 -translate-x-1/2 bg-green-600 text-white px-8 py-4 rounded-full shadow-2xl animate-bounce z-50"
                            >
                                Plano alterado com sucesso!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
