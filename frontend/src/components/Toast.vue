<template>
  <v-snackbar
  v-model="snackbar"
  :color="type"
  :timeout="timeout"
  location="top right">
    {{ message }}
  </v-snackbar>
</template>

<script>
import { mapState } from "vuex";
export default {
  computed: {
    ...mapState("toast", ["message", "type", "snackbar",'timeout']),
  },
};
</script>

