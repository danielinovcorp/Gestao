// resources/js/Pages/Encomendas/Fornecedores/columns.js
import { h } from "vue";
import { Badge } from "@/components/ui/badge";

export const columns = () => [
    {
        accessorKey: "data",
        header: "Data",
    },
    {
        accessorKey: "numero",
        header: "Número",
        cell: ({ row }) => row.original.numero || "—",
    },
    {
        accessorKey: "fornecedor.nome",
        header: "Fornecedor",
        cell: ({ row }) => row.original.fornecedor?.nome || "—",
    },
    {
        accessorKey: "origem_numero",
        header: "Origem",
        cell: ({ row }) => {
            const num = row.original.origem_numero;
            return num ? `EC-${num}` : "—";
        },
    },
    {
        accessorKey: "total",
        header: "Valor Total",
        cell: ({ row }) =>
            new Intl.NumberFormat("pt-PT", {
                style: "currency",
                currency: "EUR",
            }).format(row.original.total),
        meta: { class: "text-right font-medium" },
    },
    {
        accessorKey: "estado",
        header: "Estado",
        cell: ({ row }) => {
            const estado = row.original.estado;
            const variants = {
                rascunho: "bg-yellow-100 text-yellow-800",
                pendente: "bg-orange-100 text-orange-800",
                fechado: "bg-green-100 text-green-800",
                paga: "bg-blue-100 text-blue-800",
            };
            const label = {
                rascunho: "Rascunho",
                pendente: "Pendente",
                fechado: "Fechado",
                paga: "Paga",
            };

            return h(
                Badge,
                {
                    class: `px-2 py-1 text-xs font-medium ${variants[estado] || "bg-gray-100 text-gray-800"}`,
                },
                label[estado] || estado,
            );
        },
    },
];
