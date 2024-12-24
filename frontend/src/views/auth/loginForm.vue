<template>
  <v-sheet class="bg-image">
    <v-row class="pa-5">
      <v-col cols="12" md="6"> </v-col>
      <v-col cols="12" md="6">
        <v-card
          class="mx-auto pa-12 pb-8 pt-12"
          elevation="8"
          max-width="448"
          rounded="lg"
        >
          <v-img
            :width="150"
            aspect-ratio="16/9"
            cover
            src="../../../public/logo-client.png"
          ></v-img>
          <v-btn
            class="mb-8 mt-1"
            size="large"
            color="blue-grey-lighten-2"
            block
            @click=""
            ><v-icon icon="mdi-google"></v-icon>
            {{ $t("auth.login_with_google") }}
          </v-btn>
          <v-form v-model="valid">
            <div class="text-subtitle-1 text-medium-emphasis">
              {{ $t("auth.email") }}
            </div>

            <v-text-field
              density="compact"
              :placeholder="$t('placeholder.email')"
              prepend-inner-icon="mdi-email-outline"
              variant="outlined"
              v-model="email"
              :readonly="loading"
              :rules="emailRules"
            ></v-text-field>

            <div
              class="text-subtitle-1 text-medium-emphasis d-flex align-center justify-space-between"
            >
              {{ $t("auth.password") }}
            </div>

            <v-text-field
              :append-inner-icon="visible ? 'mdi-eye-off' : 'mdi-eye'"
              :type="visible ? 'text' : 'password'"
              density="compact"
              :placeholder="$t('placeholder.password')"
              prepend-inner-icon="mdi-lock-outline"
              variant="outlined"
              v-model="password"
              :rules="passwordRules"
              @click:append-inner="visible = !visible"
            ></v-text-field>

            <a
              class="text-caption text-decoration-none text-blue-grey-darken-2"
              href="#"
              target="_blank"
            >
              {{ $t("auth.forgot_password") }}
            </a>

            <v-btn
              class="mb-8 mt-1"
              size="large"
              variant="tonal"
              block
              :disabled="loading || !valid"
              @click="onSubmit"
            >
              {{ $t("auth.login") }}
            </v-btn>
            <v-card-text class="text-center">
              <a
                class="text-blue text-decoration-none text-blue-grey-darken-1"
                href="#"
                target="_blank"
              >
                {{ $t("auth.sign_up_now")
                }}<v-icon icon="mdi-chevron-right"></v-icon>
              </a>
            </v-card-text>
          </v-form>
        </v-card>
      </v-col>
    </v-row>
  </v-sheet>
</template>

<script>
import { constants } from "@/constants.js";
import authService from "@/services/authService";
import routePathsAdmin from "@/router/admin/routePaths";
import routePathsClient from "@/router/client/routePaths";
export default {
  data: () => ({
    visible: false,
    email: null,
    password: null,
    loading: false,
    valid: false,
  }),

  computed: {
    emailRules() {
      return constants.email;
    },
    passwordRules() {
      return constants.password;
    },
  },
  methods: {
    async onSubmit() {
      this.loading = true;
      try {
        const data = await authService.login(this.email, this.password);
        console.log("Response from API:", data); // Kiểm tra giá trị response
        if (data.role_id == 4) {
          this.$router.push(routePathsAdmin.DASHBOARD);
        } else if (data.role_id == 1) {
          this.$router.push(routePathsClient.HOMEPAGE);
        }
      } catch (error) {
        console.log("Error during login:", error); // Kiểm tra chi tiết lỗi
        this.valid = false;
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.bg-image {
  background-image: url(/src/assets/images/bg-login.jpeg);
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
  margin: 0;
  height: 100vh;
}
</style>
