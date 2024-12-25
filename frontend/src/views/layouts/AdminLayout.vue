<template>
  <v-app>
    <v-main>
      <v-row no-gutters>
        <Sidebar></Sidebar>
        <v-col>
          <Navbar></Navbar>
          <v-container class="pa-4">
           <RouterView></RouterView>
          </v-container>
        </v-col>
      </v-row>
    </v-main>
  </v-app>
</template>
<script>
import Navbar from "../admin/components/Navbar.vue";
import Sidebar from "../admin/components/Sidebar.vue";

export default {
  components: {
    Sidebar,
    Navbar,
  },
};
</script>
