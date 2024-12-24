<template>
  <v-app>
    <v-main>
      <RouterView></RouterView>
      <Toast/>
    </v-main>
  </v-app>
</template>

<script>
import Toast from './components/Toast.vue'
export default {
  name:'App',
  components:{
    Toast,
  }
}
</script>
