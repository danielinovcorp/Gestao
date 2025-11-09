<!-- resources/js/Components/Propostas/PropostaDialog.vue -->
<script setup lang="ts">
import { ref, watch, computed, nextTick } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Trash2, FileText } from "lucide-vue-next";
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

const props = defineProps<{
    open: boolean;
    onClose: () => void;
    proposta?: any;
}>();
const emit = defineEmits(["saved", "convertedToEncomenda"]);

const hoje = format(new Date(), "yyyy-MM-dd");
const isEditing = computed(() => !!props.proposta);

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
                    }
                }, 100);
            });

            if (isEditing.value && props.proposta) {
                loadPropostaData();
            }
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

// Total computado
const total = computed(() => {
    return linhas.value.reduce((a, l) => a + (l.subtotal || 0), 0);
});

// Carregar dados da proposta para edição
// ✅ Carregar dados da proposta para edição - COM CARREGAMENTO DE FORNECEDORES
const loadPropostaData = async () => {
    if (!props.proposta) return;

    form.cliente_id = props.proposta.cliente_id;
    form.data_proposta = props.proposta.data_proposta;
    form.validade = props.proposta.validade;
    form.estado = props.proposta.estado;

    if (props.proposta.linhas && props.proposta.linhas.length > 0) {
        // Primeiro, criar a estrutura básica das linhas
        const linhasCarregadas = props.proposta.linhas.map((linha: any) => ({
            artigo_id: linha.artigo_id,
            fornecedor_id: linha.fornecedor_id,
            quantidade: linha.qtd,
            preco_unitario: parseFloat(linha.preco),
            preco_custo: linha.preco_custo
                ? parseFloat(linha.preco_custo)
                : null,
            referencia: linha.artigo?.referencia || "",
            descricao: linha.descricao || linha.artigo?.nome || "",
            subtotal: parseFloat(linha.total_linha) || 0,
            fornecedor_nome: null as string | null, // Inicialmente null
        }));

        // Agora carregar os nomes dos fornecedores para cada linha
        for (let i = 0; i < linhasCarregadas.length; i++) {
            const linha = linhasCarregadas[i];
            if (linha.fornecedor_id) {
                try {
                    const nomeFornecedor = await carregarNomeFornecedor(
                        linha.fornecedor_id,
                    );
                    linha.fornecedor_nome = nomeFornecedor;
                } catch (error) {
                    console.error(
                        `Erro ao carregar fornecedor ${linha.fornecedor_id}:`,
                        error,
                    );
                    linha.fornecedor_nome = `Fornecedor ${linha.fornecedor_id}`;
                }
            }
        }

        linhas.value = linhasCarregadas;
    }
};

// ✅ Função para carregar nome do fornecedor
const carregarNomeFornecedor = async (fornecedorId: number): Promise<string | null> => {
    try {
        // Use o mesmo endpoint que o AsyncSelect usa para buscar
        let response = await fetch(`/ajax/fornecedores?q=&id=${fornecedorId}`);
        
        if (response.ok) {
            const data = await response.json();
            // Supondo que a API retorna um array de resultados
            if (Array.isArray(data) && data.length > 0) {
                return data[0].nome || data[0].descricao || `Fornecedor ${fornecedorId}`;
            }
        }
    } catch (error) {
        console.error("Erro ao carregar fornecedor:", error);
    }
    return `Fornecedor ${fornecedorId}`;
};

// ✅ Função para selecionar artigo
const selecionarArtigo = async (artigoId: number | null, index: number) => {
    const linha = linhas.value[index];
    linha.artigo_id = artigoId;

    if (artigoId) {
        try {
            // Tente diferentes endpoints
            let res;
            try {
                res = await fetch(`/api/artigos/${artigoId}`);
            } catch {
                res = await fetch(`/ajax/artigos/${artigoId}`);
            }

            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

            const data = await res.json();

            linha.preco_unitario =
                parseFloat(data.preco_venda) || parseFloat(data.preco) || 0;
            linha.referencia = data.referencia || "";
            linha.descricao = data.nome || data.descricao || "";

            updateSubtotal(index);
        } catch (e) {
            console.error("Erro ao carregar artigo:", e);
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

// ✅ Função para selecionar fornecedor - CORRIGIDA
const selecionarFornecedor = async (selectedItem: any, index: number) => {
    const linha = linhas.value[index];
    
    // Extrair o ID do item selecionado (pode ser objeto ou number)
    const fornecedorId = selectedItem?.value || selectedItem;
    
    linha.fornecedor_id = fornecedorId;
    
    if (fornecedorId) {
        // Carregar o nome do fornecedor quando selecionado
        linha.fornecedor_nome = await carregarNomeFornecedor(fornecedorId);
    } else {
        linha.fornecedor_nome = null;
    }
    
    console.log("Fornecedor selecionado:", { fornecedorId, nome: linha.fornecedor_nome });
};

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

// ✅ Adicionar nova linha - PREVINE SUBMIT
const adicionarLinha = (event?: Event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

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

// ✅ Remover linha - PREVINE SUBMIT
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

// ✅ Atualizar subtotal ao mudar quantidade ou preço
const updateSubtotal = (index: number) => {
    const linha = linhas.value[index];
    const preco = parseFloat(linha.preco_unitario) || 0;
    const quantidade = parseInt(linha.quantidade) || 0;
    linha.subtotal = preco * quantidade;
};

// ✅ Atualizar quantidade com validação
const updateQuantidade = (index: number, newValue: number) => {
    const linha = linhas.value[index];
    linha.quantidade = Math.max(1, newValue);
    updateSubtotal(index);
};

// ✅ Atualizar preço de custo
const updatePrecoCusto = (index: number, newValue: number) => {
    const linha = linhas.value[index];
    linha.preco_custo = newValue >= 0 ? newValue : null;
};

// ✅ Função para converter em encomenda (apenas para propostas fechadas em edição)
const converterParaEncomenda = async () => {
    if (!props.proposta || props.proposta.estado !== "fechado") {
        alert("Apenas propostas fechadas podem ser convertidas em encomendas.");
        return;
    }

    if (confirm("Deseja converter esta proposta em uma encomenda?")) {
        try {
            const response = await fetch(
                route("propostas.converter", props.proposta.id),
                {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") || "",
                        "Content-Type": "application/json",
                    },
                },
            );

            if (response.ok) {
                const result = await response.json();
                emit("convertedToEncomenda");
                props.onClose();
                if (result.redirect) {
                    window.location.href = result.redirect;
                }
            } else {
                alert("Erro ao converter proposta.");
            }
        } catch (error) {
            console.error("Erro:", error);
            alert("Erro ao converter proposta.");
        }
    }
};

// Submit
async function submit() {
    console.log("Iniciando submissão do formulário...");

    const clienteId = form.cliente_id ? Number(form.cliente_id) : null;
    if (!clienteId) {
        alert("Por favor, selecione um cliente.");
        return;
    }

    const linhasValidas = linhas.value.filter(
        (l) => l.artigo_id !== null && l.artigo_id !== undefined,
    );
    if (linhasValidas.length === 0) {
        alert("Adicione pelo menos um artigo à proposta.");
        return;
    }

    const linhasParaEnvio = linhasValidas.map((l) => ({
        artigo_id: Number(l.artigo_id),
        fornecedor_id: l.fornecedor_id ? Number(l.fornecedor_id) : null,
        quantidade: Number(l.quantidade),
        preco_unitario: Number(l.preco_unitario),
        preco_custo: l.preco_custo ? Number(l.preco_custo) : null,
    }));

    const fecharEGuardar =
        document.activeElement?.getAttribute("name") === "fechar";
    form.estado = fecharEGuardar ? "fechado" : "rascunho";

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

    await nextTick();

    const routeMethod = isEditing.value ? "put" : "post";
    const routeUrl = isEditing.value
        ? route("propostas.update", props.proposta.id)
        : route("propostas.store");

    form.transform((data) => ({
        ...data,
        cliente_id: clienteId,
        data_proposta: form.data_proposta,
        validade: form.validade,
        linhas: linhasParaEnvio,
        estado: form.estado,
    }))[routeMethod](routeUrl, {
        onSuccess: () => {
            console.log("✅ Proposta salva com sucesso.");
            emit("saved");
            props.onClose();
        },
        onError: (errors) => {
            console.error("❌ ERRO DE VALIDAÇÃO:", errors);
        },
        onFinish: () => {
            if (!isEditing.value) {
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
            }
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="props.onClose">
        <DialogContent class="max-w-7xl overflow-y-auto max-h-[90vh]">
            <DialogHeader>
                <DialogTitle>
                    {{ isEditing ? "Editar Proposta" : "Nova Proposta" }}
                </DialogTitle>
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
                                    <th class="px-3 py-2 text-left w-1/4">
                                        Artigo *
                                    </th>
                                    <th class="px-3 py-2 text-left w-1/5">
                                        Fornecedor
                                    </th>
                                    <th class="px-3 py-2 text-center w-20">
                                        Qtd
                                    </th>
                                    <th class="px-3 py-2 text-right w-24">
                                        Preço Venda
                                    </th>
                                    <th class="px-3 py-2 text-right w-24">
                                        Preço Custo
                                    </th>
                                    <th class="px-3 py-2 text-right w-24">
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

                                    <!-- Fornecedor -->
                                    <td class="px-3 py-2">
                                        <AsyncSelect
                                            :model-value="linha.fornecedor_id"
                                            @update:model-value="
                                                selecionarFornecedor(
                                                    $event,
                                                    index,
                                                )
                                            "
                                            fetch-url="/ajax/fornecedores?q="
                                            placeholder="Selecionar fornecedor..."
                                            class="w-full"
                                        />

                                        <!-- DEBUG: Mostrar o que está nos dados -->
                                        <div
                                            class="text-xs text-green-600 mt-1"
                                            v-if="linha.fornecedor_id"
                                        >
                                            ID: {{ linha.fornecedor_id }} |
                                            Nome:
                                            {{
                                                linha.fornecedor_nome ||
                                                "Carregando..."
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
                                            @input="
                                                (e) =>
                                                    updateQuantidade(
                                                        index,
                                                        parseInt(
                                                            e.target.value,
                                                        ) || 1,
                                                    )
                                            "
                                            class="w-full text-center"
                                        />
                                    </td>

                                    <!-- Preço Venda -->
                                    <td class="px-3 py-2 text-right font-mono">
                                        {{
                                            Number(
                                                linha.preco_unitario,
                                            ).toFixed(2)
                                        }}
                                        €
                                    </td>

                                    <!-- Preço Custo -->
                                    <td class="px-3 py-2">
                                        <Input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :value="linha.preco_custo"
                                            @input="
                                                (e) =>
                                                    updatePrecoCusto(
                                                        index,
                                                        parseFloat(
                                                            e.target.value,
                                                        ) || 0,
                                                    )
                                            "
                                            placeholder="0.00"
                                            class="w-full text-right"
                                        />
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
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="ghost"
                                            @click="
                                                (e) => removerLinha(index, e)
                                            "
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
                <DialogFooter class="flex flex-wrap gap-2">
                    <!-- Botão Converter para Encomenda (apenas para propostas fechadas em edição) -->
                    <Button
                        v-if="isEditing && proposta?.estado === 'fechado'"
                        type="button"
                        @click="converterParaEncomenda"
                        variant="secondary"
                        class="bg-green-600 hover:bg-green-700 text-white"
                    >
                        <FileText class="h-4 w-4 mr-2" />
                        Converter para Encomenda
                    </Button>

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
