<template>
  <v-container height="85vh" class="bg-grey-lighten-2 rounded-xl">
    <v-table height="80vh" fixed-header>
      <thead class="table-head">
        <tr>
          <th class="text-center">ID</th>
          <th class="text-center">Name</th>
          <th class="text-center">Calories</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in desserts" :key="item.name">
          <td class="text-center">{{ item.id }}</td>
          <td class="text-center">{{ item.name }}</td>
          <td class="text-center">{{ item.calories }}</td>
          <td class="text-center">
            <v-btn>Xóa</v-btn>
            <v-btn>Sửa</v-btn>
          </td>
        </tr>
      </tbody>
    </v-table>
  </v-container>
</template>

<script>
export default {
  data() {
    return {
      desserts: [
        {
          id:1,
          name: "Frozen Yogurt",
          calories: 159,
        },
        {
          id:2,
          name: "Ice cream sandwich",
          calories: 237,
        },
        {
          id:3,
          name: "Eclair",
          calories: 262,
        },
        {
          id:4,
          name: "Cupcake",
          calories: 305,
        },
        {
          id:5,
          name: "Gingerbread",
          calories: 356,
        },
        {
          id:6,
          name: "Jelly bean",
          calories: 375,
        },
        {
          id:7,
          name: "Lollipop",
          calories: 392,
        },
        {
          id:8,
          name: "Honeycomb",
          calories: 408,
        },
        {    id:9,
          name: "Donut",
          calories: 452,
        },
        {
          id:10,
          name: "KitKat",
          calories: 518,
        },
        {
          id:1,
          name: "Frozen Yogurt",
          calories: 159,
        },
        {
          id:2,
          name: "Ice cream sandwich",
          calories: 237,
        },
        {
          id:3,
          name: "Eclair",
          calories: 262,
        },
        {
          id:4,
          name: "Cupcake",
          calories: 305,
        },
        {
          id:5,
          name: "Gingerbread",
          calories: 356,
        },
        {
          id:6,
          name: "Jelly bean",
          calories: 375,
        },
        {
          id:7,
          name: "Lollipop",
          calories: 392,
        },
        {
          id:8,
          name: "Honeycomb",
          calories: 408,
        },
        {    id:9,
          name: "Donut",
          calories: 452,
        },
        {
          id:10,
          name: "KitKat",
          calories: 518,
        },
      ],
    };
  },
};
</script>
<style>
.table-head{
  background-color: lightblue;
}
</style>
