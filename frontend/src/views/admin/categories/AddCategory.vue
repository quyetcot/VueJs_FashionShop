<template>
  <v-container height="84vh" class="bg-grey-lighten-2 rounded-xl">
    <v-row class="pa-5">
      <span class="text-h5 font-weight-bold" color="primary">{{
        $t("admin.add_category")
      }}</span>
    </v-row>
    <v-sheet height="70vh" rounded="xl">
      <v-form @submit.prevent class="pa-5">
        <v-col>
          <v-text-field
            v-model="firstName"
            :rules="nameRules"
            variant="solo"
            :label="$t('admin.category.name')"
          ></v-text-field>
        </v-col>
        <v-col>
          <v-file-input
            :rules="rules"
            accept="image/png, image/jpeg, image/bmp"
            :label="$t('admin.category.image')"
            variant="solo"
            placeholder="Pick an avatar"
            prepend-icon="mdi-camera"
          ></v-file-input>
        </v-col>

        <v-row class="d-flex justify-center mt-2">
          <v-btn color="primary" type="submit">thêm mới</v-btn>
        </v-row>
      </v-form>
    </v-sheet>
  </v-container>
</template>
<script>
import { constants } from "@/constants";
export default {
  data: () => ({
    firstName: "",
  }),
  computed: {
    nameRules() {
      return constants.categoryName;
    },
  },
};
</script>
