import { ref } from "vue";
import axios from "axios";

type Tipo = "cliente" | "fornecedor";

export function useEntidades() {
    const rows = ref<any[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function list(params: {
        search?: string;
        tipo?: Tipo;
        per_page?: number;
        page?: number;
    }) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await axios.get("/api/entidades", { params });

            // aceita tanto array simples como estrutura paginada do Laravel
            if (Array.isArray(data)) {
                rows.value = data;
            } else if (data && Array.isArray(data.data)) {
                rows.value = data.data;
            } else {
                rows.value = [];
            }
        } catch (e: any) {
            error.value = e?.message ?? "Erro a carregar entidades";
            rows.value = [];
        } finally {
            loading.value = false;
        }
    }

    async function create(payload: any) {
        await axios.post("/api/entidades", payload);
    }

    async function update(id: number, payload: any) {
        await axios.put(`/api/entidades/${id}`, payload);
    }

    async function remove(id: number) {
        const hard = confirm(
            "Apagar PERMANENTEMENTE? (OK = permanente, Cancel = enviar para lixo)",
        );
        await axios.delete(`/api/entidades/${id}`, {
            params: { force: hard ? 1 : 0 },
        });
    }

    return { rows, list, create, update, remove, loading, error };
}
