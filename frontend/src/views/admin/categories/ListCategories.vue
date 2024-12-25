<template>
  <v-container height="84vh" class="bg-grey-lighten-2 rounded-xl">
    <v-row class="pa-5">
      <span class="text-h5 font-weight-bold" color="primary">{{
        $t("admin.categories")
      }}</span>
      <v-spacer></v-spacer>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        rounded="lg"
        :to="routePaths.ADD_CATEGORY"
      >
        {{ $t("admin.add") }}</v-btn
      >
    </v-row>
    <v-table height="72vh" fixed-header>
      <thead>
        <tr>
          <th class="text-center">{{ $t("admin.category.id") }}</th>
          <th class="text-center">{{ $t("admin.category.name") }}</th>
          <th class="text-center">{{ $t("admin.category.image") }}</th>
          <th class="text-center">{{ $t("admin.action") }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in desserts" :key="item.name">
          <td class="text-center">{{ item.id }}</td>
          <td class="text-center">{{ item.name }}</td>
          <td class="text-center">
            <v-img
              class="ma-1"
              aspect-ratio="4/3"
              cover
              rounded="xl"
              :src="item.image"
            ></v-img>
          </td>
          <td class="text-center">
            <v-btn icon="mdi-pencil" class="rounded" variant="text"></v-btn>
            <v-btn
              icon="mdi-trash-can-outline"
              variant="text"
              class="rounded"
            ></v-btn>
          </td>
        </tr>
      </tbody>
    </v-table>
  </v-container>
</template>

<script>
import routePaths from "@/router/admin/routePaths";
export default {
  data() {
    return {
      routePaths: {
        ADD_CATEGORY: routePaths.ADD_CATEGORY,
      },
      desserts: [
        {
          id: 1,
          name: "Frozen Yogurt",
          image: "https://cdn.vuetifyjs.com/images/parallax/material.jpg",
        },
        {
          id: 2,
          name: "Ice cream sandwich",
          image: "https://cdn.vuetifyjs.com/images/parallax/material.jpg",
        },
        {
          id: 3,
          name: "Eclair",
          image: "https://cdn.vuetifyjs.com/images/parallax/material.jpg",
        },
        {
          id: 4,
          name: "Cupcake",
          image: "https://cdn.vuetifyjs.com/images/parallax/material.jpg",
          calories: 305,
        },
        {
          id: 5,
          name: "Gingerbread",
          image: "https://cdn.vuetifyjs.com/images/parallax/material.jpg",
        },
        {
          id: 6,
          name: "Jelly bean",
          image: "https://cdn.vuetifyjs.com/images/parallax/material.jpg",
        },
        {
          id: 6,
          name: "Jelly bean",
          image: "https://cdn.vuetifyjs.com/images/parallax/material.jpg",
        },
      ],
    };
  },
};
</script>
<style></style>
