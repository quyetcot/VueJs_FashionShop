const state = {
  message: "",
  type: "",
  snackbar: false,
  timeout: 1000,
};
const mutations = {
  SET_TOAST(state, { message, type }) {
    (state.message = message), (state.type = type), (state.snackbar = true);
  },
  HIDE_TOAST(state) {
    state.snackbar = false;
  },
};
const actions = {
  showToast({ commit }, payload) {
    console.log(payload);
    commit("SET_TOAST", payload);
  },
  hideToast({ commit }) {
    commit("HIDE_TOAST");
  },
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
};
