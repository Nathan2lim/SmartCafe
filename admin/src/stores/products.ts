import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Product, ProductCreate, ProductUpdate } from '@/types'
import { productsService } from '@/services/products.service'

export const useProductsStore = defineStore('products', () => {
  const products = ref<Product[]>([])
  const categories = ref<string[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const lowStockProducts = computed(() => products.value.filter(p =>
    (p.stockQuantity ?? 0) <= p.lowStockThreshold
  ))

  const productsByCategory = computed(() => {
    const grouped: Record<string, Product[]> = {}
    products.value.forEach(product => {
      const category = product.category
      if (!grouped[category]) {
        grouped[category] = []
      }
      grouped[category]!.push(product)
    })
    return grouped
  })

  async function fetchProducts(): Promise<void> {
    loading.value = true
    error.value = null

    try {
      products.value = await productsService.getAll()
      updateCategories()
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors du chargement des produits'
    } finally {
      loading.value = false
    }
  }

  function updateCategories(): void {
    const uniqueCategories = new Set(products.value.map(p => p.category))
    categories.value = Array.from(uniqueCategories).sort()
  }

  async function createProduct(product: ProductCreate): Promise<Product | null> {
    error.value = null

    try {
      const newProduct = await productsService.create(product)
      products.value.push(newProduct)
      updateCategories()
      return newProduct
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors de la création du produit'
      return null
    }
  }

  async function updateProduct(id: number, product: ProductUpdate): Promise<Product | null> {
    error.value = null

    try {
      const updatedProduct = await productsService.update(id, product)
      const index = products.value.findIndex(p => p.id === id)
      if (index !== -1) {
        products.value[index] = updatedProduct
      }
      updateCategories()
      return updatedProduct
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors de la mise à jour du produit'
      return null
    }
  }

  async function deleteProduct(id: number): Promise<boolean> {
    error.value = null

    try {
      await productsService.delete(id)
      products.value = products.value.filter(p => p.id !== id)
      updateCategories()
      return true
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors de la suppression du produit'
      return false
    }
  }

  function getProductById(id: number): Product | undefined {
    return products.value.find(p => p.id === id)
  }

  return {
    products,
    categories,
    loading,
    error,
    lowStockProducts,
    productsByCategory,
    fetchProducts,
    createProduct,
    updateProduct,
    deleteProduct,
    getProductById
  }
})
