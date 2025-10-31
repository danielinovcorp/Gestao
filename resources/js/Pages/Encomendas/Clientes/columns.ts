import type { ColumnDef } from "@tanstack/vue-table";

type Row = {
    id: number;
    data: string | null;
    numero: string | null;
    validade: string | null;
    cliente: string | null;
    total: number;
    estado: "rascunho" | "fechado";
};

export const columns = (actions: {
    fechar: (id: number) => void;
    converter: (id: number) => void;
}): ColumnDef<Row>[] => [
    { accessorKey: "data", header: "Data" },
    { accessorKey: "numero", header: "Número" },
    { accessorKey: "validade", header: "Validade" },
    { accessorKey: "cliente", header: "Cliente" },
    {
        accessorKey: "total",
        header: "Valor Total",
        cell: ({ row }) =>
            new Intl.NumberFormat("pt-PT", {
                style: "currency",
                currency: "EUR",
            }).format(row.original.total),
    },
    { accessorKey: "estado", header: "Estado" },
    {
        id: "acoes",
        header: "Ações",
        cell: ({ row }) => {
            const r = row.original;
            // pequeno render plain; adapte ao teu DataTable (botões, slots, etc.)
            return h("div", { class: "flex gap-2" }, [
                r.estado === "rascunho"
                    ? h(
                          "button",
                          {
                              class: "text-indigo-600 hover:underline",
                              onClick: () => actions.fechar(r.id),
                          },
                          "Fechar",
                      )
                    : h(
                          "button",
                          {
                              class: "text-emerald-600 hover:underline",
                              onClick: () => actions.converter(r.id),
                          },
                          "Converter",
                      ),
            ]);
        },
    },
];
