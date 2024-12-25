<template>
  <v-app-bar color="white" elevation="0">
    <v-text-field
    class="ms-4"
      :loading="loading"
      append-inner-icon="mdi-magnify"
      density="compact"
      variant="outlined"
      :placeholder="$t('placeholder.search')"
      hide-details
      single-line
      @click:append-inner="onClick"
      max-width="200px"
    ></v-text-field>
    
  </v-app-bar>
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
