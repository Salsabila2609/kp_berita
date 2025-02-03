<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

// Get news data from props
const { props } = usePage();
const newsItems = ref(props.newsItems);
const allNews = ref(props.newsItems);
const activeCategory = ref(props.activeCategory || 'all');
const searchQuery = ref('');
const currentSlide = ref(0);

// Group news by date
const groupedNews = computed(() => {
  const groups = {};
  newsItems.value.forEach(news => {
    const date = new Date(news.tanggal_terbit);
    const dateKey = `${date.getDate()} ${['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][date.getMonth()]} ${date.getFullYear()}`;
    if (!groups[dateKey]) {
      groups[dateKey] = [];
    }
    groups[dateKey].push(news);
  });
  return groups;
});

// Featured news for carousel (latest 3 news items)
const featuredNews = computed(() => {
  return allNews.value.slice(0, 3);
});

// Carousel auto-rotation
const startCarousel = () => {
  setInterval(() => {
    currentSlide.value = (currentSlide.value + 1) % featuredNews.value.length;
  }, 3000);
};

// Filter news by category
const filterByCategory = (category) => {
  activeCategory.value = category;
  newsItems.value = allNews.value.filter((news) =>
    news.kategori.includes(category)
  );
};

// Search news
const searchNews = () => {
  activeCategory.value = 'all';
  newsItems.value = allNews.value.filter((news) => {
    const query = searchQuery.value.toLowerCase();
    return (
      news.judul.toLowerCase().includes(query) ||
      news.isi_berita.toLowerCase().includes(query) ||
      news.penulis.toLowerCase().includes(query)
    );
  });
};

// Delete news method
const deleteNews = async (id) => {
  if (confirm('Are you sure you want to delete this news?')) {
    router.delete(`/news/${id}`, {
      onSuccess: () => {
        newsItems.value = newsItems.value.filter(news => news.id !== id);
        allNews.value = allNews.value.filter(news => news.id !== id);
      },
    });
  }
};

// Check if category is active
const isActive = (category) => activeCategory.value === category;

// Truncate text helper
const truncateText = (text, length) => {
  if (text.length <= length) return text;
  return text.substring(0, length) + '...';
};

onMounted(() => {
  if (activeCategory.value !== 'all') {
    filterByCategory(activeCategory.value);
  }
  startCarousel();
});
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Font Import -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
      <div class="container mx-auto px-6 py-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <img src="/logo.png" alt="Logo Kabupaten Madiun" class="h-12" />
          </div>
          <div class="hidden md:flex items-center space-x-8">
            <Link href="/beranda" class="text-gray-700 hover:text-gray-900">Beranda</Link>
            <Link href="/profil" class="text-gray-700 hover:text-gray-900">Profil</Link>
            <Link href="/berita" class="text-gray-700 hover:text-gray-900">Berita</Link>
            <Link href="/informasi-publik" class="text-gray-700 hover:text-gray-900">Informasi Publik</Link>
            <Link href="/ppid" class="text-gray-700 hover:text-gray-900">PPID</Link>
            <Link href="/kontak-kami" class="text-gray-700 hover:text-gray-900">Kontak Kami</Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="bg-white border-b">
      <div class="container mx-auto px-6 py-3">
        <div class="flex items-center text-sm text-[#69ABA3]">
          <Link href="/berita" class="hover:text-[#69ABA3]">Berita</Link>
          <span class="mx-2">/</span>
          <span class="text-[#69ABA3]">Berita Daerah</span>
        </div>
      </div>
    </div>

    <!-- Page Title -->
    <div class="container mx-auto px-6 py-8">
      <Link href="/news/create" class="inline-block text-sm bg-white text-blue-600 px-4 py-2 rounded hover:bg-gray-100 mb-6">
        Create News
      </Link>
      <h1 class="text-2xl font-bold text-center text-[#E3A008]">BERITA DAERAH/PEMERINTAH</h1>
      <p class="text-center text-gray-600 mt-2">Temukan informasi menarik seputar pemerintah kabupaten madiun</p>
    </div>

    <!-- Carousel Section -->
    <div class="container mx-auto px-6 mb-12 relative">
      <div class="relative w-full h-[400px] rounded-xl overflow-hidden">
        <!-- Previous/Next Slides Hints -->
        <div class="absolute inset-0 flex">
          <div class="w-16 bg-gradient-to-r from-black/20 to-transparent"></div>
          <div class="flex-1"></div>
          <div class="w-16 bg-gradient-to-l from-black/20 to-transparent"></div>
        </div>

        <div v-for="(news, index) in featuredNews" :key="news.id"
          class="absolute w-full h-full transition-transform duration-500 ease-in-out"
          :class="{ 'translate-x-0': index === currentSlide, 'translate-x-full': index > currentSlide, '-translate-x-full': index < currentSlide }">
          <div class="relative w-full h-full rounded-xl overflow-hidden">
            <img :src="`/storage/${news.gambar_utama}`" :alt="news.judul"
              class="w-full h-full object-cover rounded-xl" />
            <!-- Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-black/50 to-black/80"></div>
            <!-- Carousel Content -->
            <div class="absolute bottom-0 left-0 right-0 p-8">
              <h2 class="text-2xl font-bold text-white mb-2">{{ news.judul }}</h2>
              <div class="text-white/90 mb-4 line-clamp-2" v-html="news.isi_berita"></div>
              
              <!-- Metadata with icons -->
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-6 text-white/90 text-sm">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ news.penulis }}
                  </div>
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ news.tanggal_terbit }}
                  </div>
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path d="M7 7h10v10H7z" />
                    </svg>
                    {{ news.kategori.join(', ') }}
                  </div>
                </div>
                <Link :href="`/news/${news.id}`" 
                      class="flex items-center gap-2 px-4 py-2 bg-[#69ABA3] text-white rounded hover:bg-[#5B9A93] text-sm">
                  <span>Selengkapnya</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Filter -->
    <div class="container mx-auto px-6 mb-8">
      <div class="flex flex-wrap gap-3">
        <!-- All News Button -->
        <Link 
          href="/news"
          class="px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200"
          :class="[activeCategory === 'all' ? 'bg-emerald-600 text-white' : 'bg-emerald-100 text-emerald-700']"
        >
          Semua Berita ({{ props.totalNewsCount }})
        </Link>
        
        <!-- Category Buttons -->
        <Link 
          v-for="category in props.categoriesWithCount"
          :key="category.category"
          :href="`/news/${category.category}`"
          class="px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200"
          :class="[isActive(category.category) ? 'bg-emerald-600 text-white' : 'bg-emerald-100 text-emerald-700']"
        >
          {{ category.category }} ({{ category.count }})
        </Link>
      </div>
    </div>

    <!-- Search Bar -->
    <div class="container mx-auto px-6 mb-12">
      <div class="max-w-md mx-auto flex">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari berita..."
          class="flex-1 px-4 py-2 border border-gray-300 rounded-l focus:outline-none focus:ring-2 focus:ring-[#69ABA3]"
        />
        <button
          @click="searchNews"
          class="px-6 py-2 bg-[#69ABA3] text-white rounded-r hover:bg-[#5B9A93] transition-colors duration-200"
        >
          Cari
        </button>
      </div>
    </div>

    <!-- News Cards -->
    <div class="container mx-auto px-6 mb-12">
      <div v-for="(newsGroup, date) in groupedNews" :key="date" class="mb-12">
        <h3 class="text-xl font-semibold text-gray-800 mb-6">{{ date }}</h3>
        <div class="space-y-6">
          <div v-for="news in newsGroup" :key="news.id"
            class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden p-4"
          >
            <div class="flex items-start gap-6">
              <div class="w-72">
                <div class="rounded-lg overflow-hidden">
                  <img 
                    v-if="news.gambar_utama"
                    :src="`/storage/${news.gambar_utama}`"
                    :alt="news.judul"
                    class="w-full h-48 object-cover"
                  />
                </div>
              </div>
              <div class="flex-1">
                <h4 class="text-2xl font-bold text-gray-900 mb-2">{{ news.judul }}</h4>
                <div class="text-gray-600 mb-3 line-clamp-3" v-html="news.isi_berita"></div>
                <p class="text-gray-600 mb-4 line-clamp-2">{{ news.excerpt }}</p>
                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 mb-4">
                  <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ news.penulis }}
                  </div>
                  <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ news.tanggal_terbit }}
                  </div>
                  <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path d="M7 7h10v10H7z" />
                    </svg>
                    {{ news.kategori.join(', ') }}
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <Link :href="`/news/${news.id}`" 
                        class="px-4 py-2 bg-[#69ABA3] text-white rounded hover:bg-[#5B9A93] text-sm">
                    Selengkapnya
                  </Link>
                  <Link :href="`/news/edit/${news.id}`"
                        class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-sm">
                    Edit
                  </Link>
                  <button @click="deleteNews(news.id)"
                          class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 text-sm">
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>