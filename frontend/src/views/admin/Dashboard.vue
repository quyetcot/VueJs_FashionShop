<template>
  <v-app>
    <v-main>
        <v-container
          height="85vh"
          class="bg-grey-lighten-2 rounded-xl"
        ></v-container>
    </v-main>
  </v-app>
</template>

<script>
import Sidebar from "./components/Sidebar.vue";
import Navbar from "./components/Navbar.vue";

export default {
  components: {
    Sidebar,
    Navbar,
  },
};
</script>
