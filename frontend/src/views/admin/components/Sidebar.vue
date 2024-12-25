<template>
  <v-navigation-drawer permanent>
    <v-list-item
      prepend-avatar="../../../../public/logo-client.png"
      title="Fashion Shop"
    >
    </v-list-item>

    <v-divider></v-divider>

    <v-list density="compact" nav>
      <v-list-item
        prepend-icon="mdi-view-dashboard"
        :title="$t('admin.dashboard')"
        value="dashboard"
        :to="routePaths.dashboard"
      ></v-list-item>

      <v-list-item
        prepend-icon="mdi-shape"
        :title="$t('admin.categories')"
        value="category"
        :to="routePaths.list_category"
      ></v-list-item>

      <v-list-item
        prepend-icon="mdi-store"
        :title="$t('admin.product')"
        value="product"
      ></v-list-item>
      <v-list-item
        prepend-icon="mdi-ticket-percent"
        :title="$t('admin.voucher')"
        value="product"
      ></v-list-item>
    </v-list>
  </v-navigation-drawer>
</template>
<script>
import routePaths from "@/router/admin/routePaths";
export default {
  data() {
    return {
      routePaths: {
        dashboard: routePaths.DASHBOARD,
        list_category: routePaths.LIST_CATEGORIES,
      },
    };
  },
};
</script>

<style>
.custom-link {
  text-decoration: none;
  color: inherit;
}
</style>
