import { ref } from "vue";
import axios from "axios";

type ListParams = { q?: string; entidade_id?: number };

function normalizePayload(p: any) {
    const out: any = { ...p };
    // coerção leve
    if (out.entidade_id != null) out.entidade_id = Number(out.entidade_id);
    if (out.funcao_id != null) out.funcao_id = Number(out.funcao_id);

    // normaliza strings vazias para null (opcionais)
    [
        "apelido",
        "telefone",
        "telemovel",
        "email",
        "cargo",
        "observacoes",
    ].forEach((k) => {
        if (out[k] === "") out[k] = null;
    });

    return out;
}

export function useContactos() {
    const rows = ref<any[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function list(params: ListParams = {}) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await axios.get("/api/contactos", {
                params,
                withCredentials: true,
            });
            // paginate: { data: [...] , ... }  |  array
            rows.value = Array.isArray(data?.data)
                ? data.data
                : Array.isArray(data)
                  ? data
                  : [];
            return data;
        } catch (e: any) {
            error.value =
                e?.response?.data?.message ||
                e?.message ||
                "Erro a carregar contactos";
            console.error(
                "❌ Erro ao carregar contactos:",
                e?.response?.data ?? e,
            );
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function create(payload: any) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await axios.post(
                "/api/contactos",
                normalizePayload(payload),
                {
                    withCredentials: true,
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                },
            );
            return data; // <- devolve o registo criado
        } catch (e: any) {
            error.value =
                e?.response?.data?.message ||
                e?.message ||
                "Erro ao criar contacto";
            console.error("❌ Erro ao criar contacto:", e?.response?.data ?? e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function update(id: number, payload: any) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await axios.put(
                `/api/contactos/${id}`,
                normalizePayload(payload),
                {
                    withCredentials: true,
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                },
            );
            return data; // <- devolve o registo atualizado
        } catch (e: any) {
            error.value =
                e?.response?.data?.message ||
                e?.message ||
                "Erro ao atualizar contacto";
            console.error(
                "❌ Erro ao atualizar contacto:",
                e?.response?.data ?? e,
            );
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function remove(id: number) {
        loading.value = true;
        error.value = null;
        try {
            await axios.delete(`/api/contactos/${id}`, {
                withCredentials: true,
            });
        } catch (e: any) {
            error.value =
                e?.response?.data?.message ||
                e?.message ||
                "Erro ao remover contacto";
            console.error(
                "❌ Erro ao remover contacto:",
                e?.response?.data ?? e,
            );
            throw e;
        } finally {
            loading.value = false;
        }
    }

    return { rows, loading, error, list, create, update, remove };
}
