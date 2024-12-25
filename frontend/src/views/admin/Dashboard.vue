<template>
  <v-container height="85vh" class="bg-grey-lighten-2 rounded-xl">
    <v-card class="mx-auto text-center"  >
      <v-card-text>
        <v-sheet color="rgba(0, 0, 0, .12)">
          <v-sparkline
            :model-value="value"
            height="100"
            padding="24"
            stroke-linecap="round"
            smooth
          >
            <template v-slot:label="item"> ${{ item.value }} </template>
          </v-sparkline>
        </v-sheet>
      </v-card-text>

      <v-card-text>
        <div class="text-h4 font-weight-thin">Sales Last 24h</div>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="justify-center">
        <v-btn variant="text" block> Go to Report </v-btn>
      </v-card-actions>
    </v-card></v-container
  >
</template>

<script>
export default {
  data: () => ({
    value: [423, 446, 675, 510, 590, 610, 760],
  }),
};
</script>
