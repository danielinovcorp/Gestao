import http from "@/lib/http";
import { ref } from "vue";

type ListParams = {
    search?: string;
    tipo?: "cliente" | "fornecedor";
    per_page?: number;
    page?: number | string;
};

type FormPayload = {
    tipo: "cliente" | "fornecedor" | "ambos";
    nome: string;
    nif?: string;
};

function mapFormToApi(payload: FormPayload, isUpdate = false) {
    // mapeia tipo → is_cliente / is_fornecedor
    const is_cliente = payload.tipo === "cliente" || payload.tipo === "ambos";
    const is_fornecedor =
        payload.tipo === "fornecedor" || payload.tipo === "ambos";

    const out: any = {
        is_cliente,
        is_fornecedor,
        nome: payload.nome,
    };

    // só envia NIF se veio preenchido (no update evitar limpar)
    if (!isUpdate && payload.nif) out.nif = payload.nif;
    if (isUpdate && payload.nif && payload.nif.trim() !== "")
        out.nif = payload.nif;

    return out;
}

export function useEntidades() {
    const rows = ref<any[]>([]);
    const loading = ref(false);
    const meta = ref<any>({});
    const links = ref<any[]>([]);

    // 👇 ajuste o URL conforme seu http.baseURL
    const URL = "/entidades"; // ou '/api/entidades' caso http NÃO tenha baseURL '/api'

    async function list(params: ListParams = {}) {
        loading.value = true;
        try {
            // compat: se alguém mandar q, converte para search
            const _params: any = { ...params };
            if ((_params as any).q && !_params.search) {
                _params.search = (params as any).q;
                delete _params.q;
            }

            const { data } = await http.get(URL, { params: _params });

            // paginate(): { data:[...], meta:{...}, links:[...] }
            if (Array.isArray(data?.data)) {
                rows.value = data.data;
                meta.value = data.meta ?? {};
                links.value = data.links ?? [];
            } else {
                // não-paginado
                rows.value = data ?? [];
                meta.value = {};
                links.value = [];
            }
        } finally {
            loading.value = false;
        }
    }

    async function create(form: FormPayload) {
        const payload = mapFormToApi(form, false);
        const { data } = await http.post(URL, payload);
        return data;
    }

    async function update(id: number, form: FormPayload) {
        const payload = mapFormToApi(form, true);
        const { data } = await http.put(`${URL}/${id}`, payload);
        return data;
    }

    async function remove(id: number) {
        const { data } = await http.delete(`${URL}/${id}`);
        return data;
    }

    return { rows, meta, links, loading, list, create, update, remove };
}
