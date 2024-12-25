<template>
  <v-app-bar color="white" elevation="0"> </v-app-bar>
</template>

<script>
export default {
  data: () => ({
    drawer: false,
  }),
  watch: {
    group() {
      this.drawer = false;
    },
  },
};
</script>
