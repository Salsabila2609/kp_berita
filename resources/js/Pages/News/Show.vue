<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

// Ambil props dari Inertia
const { props } = usePage();
const news = props.news;
const categoriesWithCount = props.categoriesWithCount;
const relatedNews = props.related;

// Format kategori berita utama
const formattedCategories = computed(() => {
  return news.kategori?.join(', ') || '';
});

// Berita terkait
const relatedNewsFiltered = computed(() => {
  return relatedNews.filter(related =>
    related.kategori.some(kategori => news.kategori.includes(kategori))
  );
});

// State untuk modal
const isModalOpen = ref(false);
const modalImage = ref('');
const modalCaption = ref('');

// Fungsi membuka modal
const openModal = (image, caption) => {
  modalImage.value = `/storage/${image}`;
  modalCaption.value = caption;
  isModalOpen.value = true;
};

// Fungsi menutup modal
const closeModal = () => {
  isModalOpen.value = false;
};
</script>


<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-blue-600 text-white shadow">
      <div class="container mx-auto px-4 py-2 flex justify-between items-center">
        <Link href="/dashboard" class="text-xl font-bold hover:underline">
          Back to Dashboard
        </Link>
      </div>
    </nav>

    <main class="container mx-auto mt-10 p-6 bg-white shadow rounded flex flex-col md:flex-row gap-6">
      <!-- Konten Utama (Kiri) -->
      <div class="flex-1 flex flex-col">
        <!-- Card Cream -->
        <div class="w-full bg-yellow-100 p-4 rounded shadow-md mb-4">
          <h1 class="text-2xl font-bold mb-2 text-gray-800">{{ news.judul }}</h1>

          <!-- Kategori dan Info -->
          <div class="flex items-center space-x-4 text-gray-600 text-sm">
            <div class="bg-blue-200 px-2 py-1 rounded text-blue-700 font-semibold">
              {{ formattedCategories }}
            </div>

            <!-- Penulis -->
            <div class="flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1 text-gray-700">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25m-7.5 3.75V5.25m12 10.5v2.25a2.25 2.25 0 01-2.25 2.25H6.75a2.25 2.25 0 01-2.25-2.25V15.75" />
              </svg>
              <span>{{ news.penulis }}</span>
            </div>

            <!-- Tanggal -->
            <div class="flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1 text-gray-700">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M6 9h12M8.25 12.75h7.5M6 15h12M8.25 18.75h7.5" />
              </svg>
              <span>{{ news.tanggal_terbit }}</span>
            </div>
          </div>
        </div>

        <!-- Gambar Utama -->
        <div class="flex flex-col mb-4">
          <img
            :src="`/storage/${news.gambar_utama}`"
            alt="Main News Image"
            class="w-full h-auto rounded object-cover"
          />
          <p class="text-gray-500 text-sm mt-2 text-center">
            {{ news.gambar_utama_keterangan }}
          </p>
        </div>
        <!-- Carousel Gambar Lampiran -->
        <div v-if="news.gambar_lampiran?.length > 0" class="mt-4">
            <h3 class="text-lg font-bold mb-4 text-gray-800">Gambar Lampiran:</h3>
            <div class="flex overflow-x-scroll space-x-4">
              <div
                v-for="(lampiran, index) in news.gambar_lampiran"
                :key="index"
                class="min-w-[200px] rounded overflow-hidden shadow cursor-pointer"
                @click="openModal(lampiran, news.gambar_lampiran_keterangan[index])"
              >
                <img :src="`/storage/${lampiran}`" :alt="`Attachment Image ${index + 1}`" class="w-full h-40 object-cover" />
                <!-- Menampilkan keterangan gambar lampiran berdasarkan indeks -->
                <p class="text-gray-500 text-xs text-center mt-2">{{ news.gambar_lampiran_keterangan[index] }}</p>
              </div>
            </div>
          </div>

          <!-- Modal -->
          <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75"
            @click="closeModal"
          >
            <div class="relative bg-white p-4 rounded shadow-lg">
              <img :src="modalImage" alt="Modal Image" class="max-w-full max-h-[80vh] object-contain" />
              <p class="text-gray-500 text-sm mt-2 text-center">{{ modalCaption }}</p>
              <button
                class="absolute top-2 right-2 text-gray-500 hover:text-gray-800"
                @click="closeModal"
              >
                ×
              </button>
            </div>
          </div>
 
        <!-- Isi Berita -->
        <div class="prose">
          <p class="text-gray-700 text-lg leading-relaxed" v-html="news.isi_berita"></p>
        </div>

      </div>

      <!-- Sidebar (Kanan) -->
      <aside class="w-full md:w-1/4 flex flex-col space-y-4">
        <!-- Kategori Berita -->
        <div class="bg-white rounded shadow-md p-4">
          <h3 class="text-lg font-bold mb-4 text-gray-800">Kategori Berita:</h3>
          <div class="space-y-2">
            <Link
              v-for="(category, index) in categoriesWithCount"
              :key="index"
              :href="`/news/${category.category}`"
              class="block text-sm text-white px-4 py-2 mb-2 rounded-md bg-blue-500 hover:bg-blue-600 transition"
            >
              {{ category.category }} ({{ category.count }})
            </Link>
          </div>
        </div>


        <!-- Berita Serupa -->
        <div class="bg-white rounded shadow-md p-4">
          <h3 class="text-lg font-bold mb-4 text-gray-800">Berita Serupa:</h3>
          <div v-for="berita in relatedNews" :key="berita.id" class="bg-white rounded shadow-md overflow-hidden mb-4">
            <Link :href="`/news/${berita.id}`" class="block">
              <img :src="`/storage/${berita.gambar_utama}`" alt="Berita Image" class="w-full h-32 object-cover" />
              <div class="p-4">
                <h4 class="font-bold text-gray-800 text-sm">
                  {{ berita.judul }}
                </h4>
                <p class="text-sm text-gray-600 mt-1">
                  {{ berita.penulis }} - {{ berita.tanggal_terbit }}
                </p>
              </div>
            </Link>
          </div>
        </div>
      </aside>
    </main>
  </div>
</template>

<style>
ul, ol {
padding: 0 1rem;

list-style-position: outside;
}

ul {
  list-style-type: disc; 
}

ol {
  list-style-type: decimal; 
}

h1 {
  font-size: 1.4rem;
}

h2 {
  font-size: 1.2rem;
}

h3 {
  font-size: 1.1rem;
}

h4,
h5,
h6 {
  font-size: 1rem;
}

/* Code and preformatted text styles */
code {
  background-color: var(--purple-light);
  border-radius: 0.4rem;
  color: var(--black);
  font-size: 0.85rem;
  padding: 0.25em 0.3em;
}

pre {
  background: var(--black);
  border-radius: 0.5rem;
  color: var(--white);
  font-family: 'JetBrainsMono', monospace;
  margin: 1.5rem 0;
  padding: 0.75rem 1rem;

  code {
    background: none;
    color: inherit;
    font-size: 0.8rem;
    padding: 0;
  }
}

blockquote {
  border-left: 3px solid var(--gray-3);
  margin: 1.5rem 0;
  padding-left: 1rem;
}

hr {
  border: none;
  border-top: 1px solid var(--gray-2);
  margin: 2rem 0;
}
</style>