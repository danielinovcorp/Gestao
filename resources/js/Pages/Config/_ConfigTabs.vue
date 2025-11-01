<script setup lang="ts">
import NavLink from "@/Components/NavLink.vue";
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <NavLink
            :href="route('config.paises.index')"
            :active="route().current('config.paises.*')"
        >
            Países
        </NavLink>

        <NavLink
            :href="route('config.funcoes-contacto.index')"
            :active="route().current('config.funcoes-contacto.*')"
        >
            Contactos
        </NavLink>

        <NavLink
            :href="route('config.calendario.tipos.index')"
            :active="
                route().current('config.calendario.tipos.*') ||
                route().current('config.calendario.acoes.*')
            "
        >
            Calendário
        </NavLink>

        <NavLink
            :href="route('config.artigos.index')"
            :active="route().current('config.artigos.*')"
        >
            Artigos
        </NavLink>

        <NavLink
            :href="route('config.iva.index')"
            :active="route().current('config.iva.*')"
        >
            IVA
        </NavLink>

        <NavLink
            :href="route('logs.index')"
            :active="route().current('logs.*')"
        >
            Logs
        </NavLink>

        <NavLink
            :href="route('config.empresa')"
            :active="route().current('config.empresa')"
        >
            Empresa
        </NavLink>
    </div>
</template>
