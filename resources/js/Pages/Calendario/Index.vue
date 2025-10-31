<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, watch } from "vue";

// shadcn-vue
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Textarea } from "@/components/ui/textarea";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";

// FullCalendar
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";

const apiBase = "/api/calendar/events";
const calendarRef = ref<any>();

// filtros
const filtroUserId = ref<string>("");
const filtroEntidadeId = ref<string>("");

// modal form
const open = ref(false);
const form = ref<any>({
    id: null,
    start: "",
    duration_minutes: 60,
    user_id: undefined,
    entidade_id: undefined,
    calendar_type_id: undefined,
    calendar_action_id: undefined,
    estado: "agendado",
    descricao: "",
});

// carregar opções (substituir por props via Inertia ou endpoints próprios)
const users = ref<{ id: number; name: string }[]>([]);
const entidades = ref<{ id: number; nome: string }[]>([]);
const types = ref<{ id: number; nome: string }[]>([]);
const actions = ref<{ id: number; nome: string }[]>([]);

function getCalendar() {
    return calendarRef.value?.getApi?.();
}

async function fetchEvents(info: any, success: any, failure: any) {
    try {
        const params = new URLSearchParams({
            start: info.startStr,
            end: info.endStr,
        });
        if (filtroUserId.value !== "all")
            params.append("user_id", filtroUserId.value);

        if (filtroEntidadeId.value !== "all")
            params.append("entidade_id", filtroEntidadeId.value);

        const res = await fetch(`${apiBase}?${params.toString()}`, {
            credentials: "same-origin",
            headers: { Accept: "application/json" },
        });

        if (!res.ok) {
            const text = await res.text(); // pode ser HTML
            console.error("Calendar feed error", res.status, text);
            failure(new Error(`Calendar feed ${res.status}`));
            return;
        }

        const data = await res.json();
        success(data.data ?? data);
    } catch (e) {
        failure(e);
    }
}

function refetch() {
    getCalendar()?.refetchEvents();
}
watch([filtroUserId, filtroEntidadeId], refetch);

function newEvent(dateStr?: string) {
    open.value = true;
    form.value = {
        id: null,
        start: dateStr || new Date().toISOString().slice(0, 16),
        duration_minutes: 60,
        estado: "agendado",
        descricao: "",
    };
}

function editEvent(arg: any) {
    const ev = arg.event;
    open.value = true;
    form.value = {
        id: Number(ev.id),
        start: ev.start?.toISOString().slice(0, 16),
        duration_minutes: Math.max(5, Math.round((ev.end - ev.start) / 60000)),
        descricao: ev.extendedProps?.descricao || "",
        estado: ev.extendedProps?.estado || "agendado",
    };
}

async function save() {
    const payload: any = { ...form.value };

    // normalizar opcionais
    for (const k of [
        "user_id",
        "entidade_id",
        "calendar_type_id",
        "calendar_action_id",
    ]) {
        if (payload[k] === "" || payload[k] === undefined) payload[k] = null;
    }

    const method = payload.id ? "PUT" : "POST";
    const url = payload.id ? `${apiBase}/${payload.id}` : apiBase;

    const res = await fetch(url, {
        method,
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        credentials: "same-origin",
        body: JSON.stringify(payload),
    });

    if (!res.ok) {
        const text = await res.text();
        alert(`Erro ao guardar (${res.status})`);
        console.error("save error", text);
        return;
    }

    open.value = false;
    refetch();
}

async function removeEvent() {
    if (!form.value.id) return;
    if (!confirm("Apagar este registo?")) return;
    const res = await fetch(`${apiBase}/${form.value.id}`, {
        method: "DELETE",
        credentials: "same-origin",
    });
    if (!res.ok) {
        alert("Falha ao apagar");
        return;
    }
    open.value = false;
    refetch();
}

const calendarOptions: any = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: "timeGridWeek",
    headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "dayGridMonth,timeGridWeek,timeGridDay",
    },
    selectable: true,
    selectMirror: true,
    navLinks: true,
    editable: true,
    eventSources: [fetchEvents],
    select(info: any) {
        newEvent(info.startStr.slice(0, 16));
    },
    eventClick: editEvent,
    eventDrop(info: any) {
        editEvent({ event: info.event });
        save();
    },
    eventResize(info: any) {
        editEvent({ event: info.event });
        save();
    },
};
</script>

<template>
    <Head title="Calendário" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight">Calendário</h2>
                <Button @click="newEvent()">Novo</Button>
            </div>
        </template>

        <div class="space-y-4 p-4">
            <!-- Filtros -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <Select v-model="filtroUserId">
                    <SelectTrigger>
                        <SelectValue placeholder="Filtrar por Utilizador" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos</SelectItem>
                        <SelectItem
                            v-for="u in users"
                            :key="u.id"
                            :value="String(u.id)"
                            >{{ u.name }}</SelectItem
                        >
                    </SelectContent>
                </Select>

                <Select v-model="filtroEntidadeId">
                    <SelectTrigger>
                        <SelectValue placeholder="Filtrar por Entidade" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todas</SelectItem>
                        <SelectItem
                            v-for="e in entidades"
                            :key="e.id"
                            :value="String(e.id)"
                            >{{ e.nome }}</SelectItem
                        >
                    </SelectContent>
                </Select>

                <div />
            </div>

            <!-- Calendário -->
            <div class="bg-white dark:bg-neutral-900 rounded-2xl p-2 shadow">
                <FullCalendar ref="calendarRef" :options="calendarOptions" />
            </div>
        </div>

        <!-- Modal criar/editar -->
        <Dialog v-model:open="open">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle
                        >{{ form.id ? "Editar" : "Novo" }} Evento</DialogTitle
                    >
                </DialogHeader>

                <div class="grid gap-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <Label>Data & Hora</Label>
                            <Input type="datetime-local" v-model="form.start" />
                        </div>
                        <div>
                            <Label>Duração (min.)</Label>
                            <Input
                                type="number"
                                min="5"
                                max="1440"
                                v-model.number="form.duration_minutes"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <Label>Responsável (Utilizador)</Label>
                            <Select v-model="form.user_id">
                                <SelectTrigger
                                    ><SelectValue placeholder="Selecione"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">—</SelectItem>
                                    <SelectItem
                                        v-for="u in users"
                                        :key="u.id"
                                        :value="u.id"
                                        >{{ u.name }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Entidade</Label>
                            <Select v-model="form.entidade_id">
                                <SelectTrigger
                                    ><SelectValue placeholder="Selecione"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">—</SelectItem>
                                    <SelectItem
                                        v-for="e in entidades"
                                        :key="e.id"
                                        :value="e.id"
                                        >{{ e.nome }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <Label>Tipo</Label>
                            <Select v-model="form.calendar_type_id">
                                <SelectTrigger
                                    ><SelectValue placeholder="Selecione"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">—</SelectItem>
                                    <SelectItem
                                        v-for="t in types"
                                        :key="t.id"
                                        :value="t.id"
                                        >{{ t.nome }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Ação</Label>
                            <Select v-model="form.calendar_action_id">
                                <SelectTrigger
                                    ><SelectValue placeholder="Selecione"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">—</SelectItem>
                                    <SelectItem
                                        v-for="a in actions"
                                        :key="a.id"
                                        :value="a.id"
                                        >{{ a.nome }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div>
                        <Label>Descrição</Label>
                        <Textarea
                            rows="4"
                            v-model="form.descricao"
                            placeholder="Detalhes…"
                        />
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <Label>Estado</Label>
                            <RadioGroup
                                v-model="form.estado"
                                class="flex gap-4 mt-2"
                            >
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem
                                        value="agendado"
                                        id="st1"
                                    /><Label for="st1">Agendado</Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem
                                        value="concluido"
                                        id="st2"
                                    /><Label for="st2">Concluído</Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem
                                        value="cancelado"
                                        id="st3"
                                    /><Label for="st3">Cancelado</Label>
                                </div>
                            </RadioGroup>
                        </div>
                    </div>

                    <div class="flex justify-between pt-2">
                        <Button
                            variant="destructive"
                            v-if="form.id"
                            @click="removeEvent"
                            >Apagar</Button
                        >
                        <div class="ml-auto space-x-2">
                            <Button variant="secondary" @click="open = false"
                                >Cancelar</Button
                            >
                            <Button @click="save">Guardar</Button>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>

<style scoped>
:deep(.fc) {
    --fc-border-color: rgba(125, 125, 125, 0.2);
}
</style>
