import { h } from "vue";

interface Column {
    accessorKey?: string;
    header: string;
    id?: string;
    cell?: (ctx: { row: { original: any } }) => any;
}

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
}): Column[] => [
    {
        accessorKey: "data",
        header: "Data",
        cell: ({ row }) => h("span", row.original.data || "-"),
    },
    {
        accessorKey: "numero",
        header: "Número",
        cell: ({ row }) => h("span", row.original.numero || "-"),
    },
    {
        accessorKey: "validade",
        header: "Validade",
        cell: ({ row }) => h("span", row.original.validade || "-"),
    },
    {
        accessorKey: "cliente",
        header: "Cliente",
        cell: ({ row }) => h("span", row.original.cliente || "-"),
    },
    {
        accessorKey: "total",
        header: "Valor Total",
        cell: ({ row }) =>
            h(
                "span",
                new Intl.NumberFormat("pt-PT", {
                    style: "currency",
                    currency: "EUR",
                }).format(row.original.total),
            ),
    },
    {
        accessorKey: "estado",
        header: "Estado",
        cell: ({ row }) => {
            const estado = row.original.estado;
            const colorClass =
                estado === "fechado"
                    ? "bg-green-100 text-green-800"
                    : "bg-yellow-100 text-yellow-800";

            return h(
                "span",
                {
                    class: `px-2 py-1 rounded-full text-xs ${colorClass}`,
                },
                estado,
            );
        },
    },
    {
        id: "acoes",
        header: "Ações",
        cell: ({ row }) => {
            const r = row.original;
            return h("div", { class: "flex gap-2" }, [
                r.estado === "rascunho"
                    ? h(
                          "button",
                          {
                              class: "text-indigo-600 hover:underline text-sm",
                              onClick: () => actions.fechar(r.id),
                          },
                          "Fechar",
                      )
                    : h(
                          "button",
                          {
                              class: "text-emerald-600 hover:underline text-sm",
                              onClick: () => actions.converter(r.id),
                          },
                          "Converter",
                      ),
            ]);
        },
    },
];
