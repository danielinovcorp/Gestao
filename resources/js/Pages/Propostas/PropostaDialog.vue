<script setup lang="ts">
import { ref, watch, computed, nextTick } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Trash2 } from "lucide-vue-next";
import { format } from "date-fns";
import {
    Form,
    FormField,
    FormItem,
    FormLabel,
    FormControl,
    FormMessage,
    FormDescription,
} from "@/Components/ui/form";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import AsyncSelect from "@/Components/AsyncSelect.vue";

const { errors } = useForm();

const props = defineProps<{ open: boolean; onClose: () => void }>();
const emit = defineEmits(["saved"]);

const hoje = format(new Date(), "yyyy-MM-dd");

// Foco no campo Cliente
const clienteInputRef = ref<any>(null);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            nextTick(() => {
                setTimeout(() => {
                    const input =
                        clienteInputRef.value?.$el?.querySelector("input");
                    if (input) {
                        input.focus();
                        console.log(
                            "Autofocus: Foco aplicado no campo Cliente.",
                        );
                    }
                }, 100);
            });
        }
    },
);

// Formulário Inertia
const form = useForm({
    cliente_id: null as number | null,
    data_proposta: hoje,
    validade: "",
    estado: "rascunho",
    linhas: [] as any[],
});

// Linhas de artigos (estado local)
const linhas = ref<any[]>([
    {
        artigo_id: null,
        fornecedor_id: null,
        quantidade: 1,
        preco_unitario: 0,
        preco_custo: null,
        referencia: "",
        descricao: "",
        subtotal: 0,
    },
]);

// ✅ CORREÇÃO: computed retorna NÚMERO, não string
const total = computed(() => {
    return linhas.value.reduce((a, l) => a + (l.subtotal || 0), 0);
});

// ✅ CORREÇÃO: Função para selecionar artigo - MELHORADA
const selecionarArtigo = async (artigoId: number | null, index: number) => {
    const linha = linhas.value[index];
    linha.artigo_id = artigoId;

    if (artigoId) {
        try {
            console.log("🔍 Buscando artigo ID:", artigoId);
            
            // ✅ CORREÇÃO: Use a rota correta para buscar artigo individual
            const res = await fetch(`/api/artigos/${artigoId}`);
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const data = await res.json();
            console.log("📦 Dados do artigo recebidos:", data);
            
            // ✅ CORREÇÃO: Ajuste para os campos corretos da API
            linha.preco_unitario = parseFloat(data.preco_venda) || parseFloat(data.preco) || 0;
            linha.referencia = data.referencia || "";
            linha.descricao = data.nome || data.descricao || "";
            
            console.log("💰 Preço unitário definido:", linha.preco_unitario);
            
            updateSubtotal(index);
        } catch (e) {
            console.error("❌ Erro ao carregar artigo:", e);
            // ✅ CORREÇÃO: Valores padrão em caso de erro
            linha.preco_unitario = 0;
            linha.referencia = "";
            linha.descricao = "";
            linha.subtotal = 0;
        }
    } else {
        linha.preco_unitario = 0;
        linha.referencia = "";
        linha.descricao = "";
        linha.subtotal = 0;
    }
};

// ✅ DEBUG: Monitora mudanças nos valores
watch(
    () => form.cliente_id,
    (newVal) => {
        console.log(
            "🔍 PropostaDialog - cliente_id alterado:",
            newVal,
            "Tipo:",
            typeof newVal,
        );
    },
);

watch(
    () => linhas.value,
    (newVal) => {
        console.log(
            "🔍 PropostaDialog - linhas alteradas:",
            newVal.map((l) => ({
                artigo_id: l.artigo_id,
                preco_unitario: l.preco_unitario,
                quantidade: l.quantidade,
                subtotal: l.subtotal,
            })),
        );
        
        // ✅ DEBUG: Verifica o total
        console.log("💰 Total calculado:", total.value);
    },
    { deep: true },
);

// Calcula validade automática
watch(
    () => form.data_proposta,
    (data) => {
        if (data) {
            const dataProposta = new Date(data);
            const validade = new Date(dataProposta);
            validade.setDate(validade.getDate() + 30);
            form.validade = format(validade, "yyyy-MM-dd");
        }
    },
    { immediate: true },
);

// ✅ CORREÇÃO: Adicionar nova linha - PREVINE SUBMIT
const adicionarLinha = (event?: Event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    console.log("➕ Adicionando nova linha...");
    
    linhas.value.push({
        artigo_id: null,
        fornecedor_id: null,
        quantidade: 1,
        preco_unitario: 0,
        preco_custo: null,
        referencia: "",
        descricao: "",
        subtotal: 0,
    });
};

// ✅ CORREÇÃO: Remover linha - PREVINE SUBMIT
const removerLinha = (index: number, event?: Event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    if (linhas.value.length > 1) {
        linhas.value.splice(index, 1);
    } else {
        alert("Pelo menos uma linha é necessária.");
    }
};

// ✅ CORREÇÃO: Atualizar subtotal ao mudar quantidade ou preço
const updateSubtotal = (index: number) => {
    const linha = linhas.value[index];
    const preco = parseFloat(linha.preco_unitario) || 0;
    const quantidade = parseInt(linha.quantidade) || 0;
    linha.subtotal = preco * quantidade;
    
    console.log(`🔄 Subtotal linha ${index}:`, {
        preco,
        quantidade,
        subtotal: linha.subtotal
    });
};

// ✅ CORREÇÃO: Atualizar quantidade com validação
const updateQuantidade = (index: number, newValue: number) => {
    const linha = linhas.value[index];
    linha.quantidade = Math.max(1, newValue); // Mínimo 1
    updateSubtotal(index);
};

// Submit
async function submit() {
    console.log("1. Iniciando submissão do formulário...");

    // DEBUG: Valores brutos
    console.log("1.1 VALORES BRUTOS:", {
        cliente_id: form.cliente_id,
        tipo_cliente_id: typeof form.cliente_id,
        linhas_originais: linhas.value.map((l) => ({
            artigo_id: l.artigo_id,
            preco_unitario: l.preco_unitario,
            quantidade: l.quantidade,
            subtotal: l.subtotal,
        })),
        total: total.value
    });

    // Garante cliente_id numérico
    const clienteId = form.cliente_id ? Number(form.cliente_id) : null;
    if (!clienteId) {
        alert("Por favor, selecione um cliente.");
        return;
    }
    form.cliente_id = clienteId;

    // Filtra linhas válidas
    const linhasValidas = linhas.value.filter(
        (l) => l.artigo_id !== null && l.artigo_id !== undefined,
    );
    if (linhasValidas.length === 0) {
        alert("Adicione pelo menos um artigo à proposta.");
        return;
    }

    // Prepara linhas para envio
    const linhasParaEnvio = linhasValidas.map((l) => {
        const artigoId = Number(l.artigo_id);
        const fornecedorId = l.fornecedor_id ? Number(l.fornecedor_id) : null;

        console.log("📦 Linha convertida:", {
            artigoId,
            fornecedorId,
            qtd: Number(l.quantidade),
            preco: Number(l.preco_unitario),
            subtotal: l.subtotal
        });

        return {
            artigo_id: artigoId,
            fornecedor_id: fornecedorId,
            quantidade: Number(l.quantidade),
            preco_unitario: Number(l.preco_unitario),
            preco_custo: l.preco_custo ? Number(l.preco_custo) : null,
        };
    });

    // Define estado
    const fecharEGuardar =
        document.activeElement?.getAttribute("name") === "fechar";
    form.estado = fecharEGuardar ? "fechado" : "rascunho";

    // PREENCHE DATAS AUTOMATICAMENTE AO FECHAR
    if (fecharEGuardar) {
        if (!form.data_proposta) {
            form.data_proposta = hoje;
        }
        if (!form.validade) {
            const data = new Date(form.data_proposta);
            const validade = new Date(data);
            validade.setDate(validade.getDate() + 30);
            form.validade = format(validade, "yyyy-MM-dd");
        }
    }

    console.log("2. DADOS FINAIS PARA ENVIO:", {
        cliente_id: form.cliente_id,
        data_proposta: form.data_proposta,
        validade: form.validade,
        linhas: linhasParaEnvio,
        estado: form.estado,
        total: total.value
    });

    // Garante sincronia com DOM
    await nextTick();

    // TRANSFORM: Garante que os dados cheguem ao backend
    form.transform((data) => ({
        ...data,
        cliente_id: clienteId,
        data_proposta: form.data_proposta,
        validade: form.validade,
        linhas: linhasParaEnvio,
        estado: form.estado,
        _token: document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content"),
    })).post(route("propostas.store"), {
        onSuccess: () => {
            console.log("✅ SUCESSO! Proposta salva com sucesso.");
            emit("saved");
            props.onClose();
        },
        onError: (errors) => {
            console.error("❌ ERRO DE VALIDAÇÃO:", errors);
            form.estado = "rascunho";
        },
        onFinish: () => {
            console.log("🏁 Submissão finalizada.");
            form.reset();
            form.data_proposta = hoje;
            linhas.value = [
                {
                    artigo_id: null,
                    fornecedor_id: null,
                    quantidade: 1,
                    preco_unitario: 0,
                    preco_custo: null,
                    referencia: "",
                    descricao: "",
                    subtotal: 0,
                },
            ];
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="props.onClose">
        <DialogContent class="max-w-5xl overflow-y-auto max-h-[90vh]">
            <DialogHeader>
                <DialogTitle>Nova Proposta</DialogTitle>
                <DialogDescription>
                    Crie uma nova proposta comercial. A validade é de 30 dias a
                    partir da data da proposta.
                </DialogDescription>
            </DialogHeader>

            <Form @submit="submit" class="space-y-6">
                <!-- CLIENTE -->
                <FormField name="cliente_id">
                    <FormItem>
                        <FormLabel>Cliente *</FormLabel>
                        <FormControl>
                            <AsyncSelect
                                ref="clienteInputRef"
                                fetch-url="/ajax/clientes?q="
                                :model-value="form.cliente_id"
                                @update:modelValue="form.cliente_id = $event"
                                placeholder="Pesquisar cliente por nome ou NIF..."
                            />
                        </FormControl>
                        <FormMessage />

                        <!-- DEBUG melhorado -->
                        <div
                            v-if="form.cliente_id"
                            class="text-xs text-green-600 mt-1"
                        >
                            ✅ Cliente selecionado (Valor:
                            {{ form.cliente_id }}, Tipo:
                            {{ typeof form.cliente_id }})
                        </div>
                        <div v-else class="text-xs text-red-500 mt-1">
                            ❌ Nenhum cliente selecionado
                        </div>

                        <p
                            v-if="form.errors.cliente_id"
                            class="text-sm font-medium text-red-600 mt-1"
                        >
                            {{ form.errors.cliente_id }}
                        </p>
                    </FormItem>
                </FormField>

                <!-- Datas -->
                <div class="grid grid-cols-2 gap-4">
                    <FormField name="data_proposta">
                        <FormItem>
                            <FormLabel>Data da Proposta</FormLabel>
                            <FormControl>
                                <Input
                                    type="date"
                                    v-model="form.data_proposta"
                                />
                            </FormControl>
                            <FormDescription>
                                Data em que a proposta é finalizada (estado
                                Fechado).
                            </FormDescription>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <FormField name="validade">
                        <FormItem>
                            <FormLabel>Validade</FormLabel>
                            <FormControl>
                                <Input type="date" v-model="form.validade" />
                            </FormControl>
                            <FormDescription>
                                Padrão: 30 dias após a data da proposta.
                            </FormDescription>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                </div>

                <!-- Linhas de Artigos -->
                <div class="mt-6">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-medium text-slate-700">
                            Linhas de Artigos
                        </h3>
                        <!-- ✅ CORREÇÃO: Button com type="button" para prevenir submit -->
                        <Button
                            type="button"
                            size="sm"
                            @click="adicionarLinha"
                            variant="outline"
                        >
                            Adicionar Linha
                        </Button>
                    </div>

                    <div class="border rounded-lg overflow-visible">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b">
                                <tr>
                                    <th class="px-3 py-2 text-left">
                                        Artigo *
                                    </th>
                                    <th class="px-3 py-2 text-center w-24">
                                        Qtd
                                    </th>
                                    <th class="px-3 py-2 text-right w-28">
                                        Preço
                                    </th>
                                    <th class="px-3 py-2 text-right w-28">
                                        Subtotal
                                    </th>
                                    <th class="px-3 py-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(linha, index) in linhas"
                                    :key="index"
                                    class="border-b hover:bg-slate-50"
                                >
                                    <!-- Artigo -->
                                    <td class="px-3 py-2">
                                        <AsyncSelect
                                            :model-value="linha.artigo_id"
                                            @update:model-value="
                                                selecionarArtigo($event, index)
                                            "
                                            fetch-url="/ajax/artigos?q="
                                            placeholder="Ref ou nome..."
                                            class="w-full"
                                        />
                                        <div
                                            v-if="
                                                errors[
                                                    `linhas.${index}.artigo_id`
                                                ]
                                            "
                                            class="text-red-600 text-xs mt-1"
                                        >
                                            {{
                                                errors[
                                                    `linhas.${index}.artigo_id`
                                                ]
                                            }}
                                        </div>
                                    </td>

                                    <!-- Quantidade -->
                                    <td class="px-3 py-2">
                                        <Input
                                            type="number"
                                            min="1"
                                            step="1"
                                            :value="linha.quantidade"
                                            @input="(e) => updateQuantidade(index, parseInt(e.target.value) || 1)"
                                            class="w-full text-center"
                                        />
                                    </td>

                                    <!-- Preço Unitário -->
                                    <td class="px-3 py-2 text-right font-mono">
                                        {{
                                            Number(
                                                linha.preco_unitario,
                                            ).toFixed(2)
                                        }}
                                        €
                                    </td>

                                    <!-- Subtotal -->
                                    <td
                                        class="px-3 py-2 text-right font-medium"
                                    >
                                        {{ Number(linha.subtotal).toFixed(2) }}
                                        €
                                    </td>

                                    <!-- Remover -->
                                    <td class="px-3 py-2 text-center">
                                        <!-- ✅ CORREÇÃO: Button com type="button" -->
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="ghost"
                                            @click="(e) => removerLinha(index, e)"
                                            class="h-8 w-8 text-red-600 hover:bg-red-50"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Total -->
                    <div class="mt-4 flex justify-end">
                        <div class="text-lg font-bold text-slate-800">
                            Total: {{ total.toFixed(2) }} €
                        </div>
                    </div>
                </div>

                <!-- Botões -->
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="props.onClose"
                        :disabled="form.processing"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing || !form.cliente_id"
                    >
                        <span v-if="form.processing">A guardar...</span>
                        <span v-else>Guardar como Rascunho</span>
                    </Button>
                    <Button
                        type="submit"
                        name="fechar"
                        value="1"
                        variant="default"
                        :disabled="form.processing || !form.cliente_id"
                    >
                        <span v-if="form.processing">A guardar...</span>
                        <span v-else>Fechar e Guardar</span>
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
