import apis from './apis'
import apiWeb from './apiWeb'

const categoryService = {
  async getCategories () {
    const response = await apiWeb.get(apis.getCagories)
    console.log(response)
    return response
  }
}
export default categoryService;
