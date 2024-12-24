<template>
  <v-snackbar
  v-model="snackbar"
  :color="type"
  top>
    {{ message }}
  </v-snackbar>
</template>

<script>
import { mapState } from "vuex";
export default {
  computed: {
    ...mapState("toast", ["message", "type", "snackbar", "timeout"]),
  },
};
</script>

<style>
/* .toast {
  position: fixed;
  left: 50%;
  transform: translateX(-50%);
  padding: 10px 20px;
  border-radius: 4px;
  color: #fff;
  font-size: 16px;
  z-index: 9999;
} */
</style>
