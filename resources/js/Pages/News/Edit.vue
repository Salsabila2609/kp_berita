<script setup>
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import MyEditor from '@/Components/MyEditor.vue';

const props = defineProps({
  news: {
    type: Object,
    required: true
  }
});

const categories = [
  'Pemerintah',
  'Berita Daerah', 
  'Umum',
  'Ekonomi',
  'Seni Budaya dan Hiburan',
  'Lowongan',
];

const form = useForm({
  _method: 'PUT',  // Add this for proper method spoofing
  penulis: props.news.penulis,
  judul: props.news.judul,
  isi_berita: props.news.isi_berita,
  kategori: props.news.kategori || [],
  gambar_utama: null,
  gambar_utama_keterangan: props.news.gambar_utama_keterangan || '',
  gambar_lampiran: [],
  gambar_lampiran_keterangan: [],
  kept_attachments: props.news.gambar_lampiran || [],
  kept_attachments_keterangan: props.news.gambar_lampiran_keterangan || [],
});

const submit = () => {
  // Convert categories to JSON string
  const formKategori = JSON.stringify(form.kategori);
  
  // Create a new FormData instance
  const formData = new FormData();
  
  // Append all form fields
  formData.append('_method', 'PUT');
  formData.append('penulis', form.penulis);
  formData.append('judul', form.judul);
  formData.append('isi_berita', form.isi_berita);
  formData.append('kategori', formKategori);
  formData.append('gambar_utama_keterangan', form.gambar_utama_keterangan);

  // Handle main image
  if (form.gambar_utama instanceof File) {
    formData.append('gambar_utama', form.gambar_utama);
  }

  // Handle kept attachments
  form.kept_attachments.forEach((path, index) => {
    formData.append(`kept_attachments[]`, path);
    formData.append(`kept_attachments_keterangan[]`, form.kept_attachments_keterangan[index] || '');
  });

  // Handle new attachments
  form.gambar_lampiran.forEach((file, index) => {
    if (file instanceof File) {
      formData.append(`gambar_lampiran[]`, file);
      formData.append(`gambar_lampiran_keterangan[]`, form.gambar_lampiran_keterangan[index] || '');
    }
  });

  // Submit using Inertia's form helper with the FormData
  form.post(`/news/${props.news.id}`, {
    forceFormData: true,
    onSuccess: () => {
      // Optional: Add success handling
    },
    preserveScroll: true,
  });
};

const addImage = (event) => {
  const files = event.target.files;
  if (files.length > 0) {
    form.gambar_lampiran.push(files[0]);
    form.gambar_lampiran_keterangan.push('');
  }
};

const removeExistingImage = (index) => {
  form.kept_attachments.splice(index, 1);
  form.kept_attachments_keterangan.splice(index, 1);
};

const removeNewImage = (index) => {
  form.gambar_lampiran.splice(index, 1);
  form.gambar_lampiran_keterangan.splice(index, 1);
};

const previewImage = (file) => {
  return file instanceof File ? URL.createObjectURL(file) : `/storage/${file}`;
};
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-blue-600 text-white shadow">
      <div class="container mx-auto px-4 py-2 flex justify-between items-center">
        <Link href="/news" class="text-xl font-bold hover:underline">
          Back to News
        </Link>
      </div>
    </nav>

    <main class="container mx-auto mt-10 p-6 bg-white shadow rounded">
      <h1 class="text-2xl font-bold text-center mb-6">Edit News</h1>

      <form @submit.prevent="submit" enctype="multipart/form-data">
        <!-- Penulis -->
        <div class="mb-4">
          <label for="penulis" class="block text-sm font-semibold text-gray-700">Penulis</label>
          <input
            id="penulis"
            v-model="form.penulis"
            type="text"
            class="mt-1 block w-full p-2 border border-gray-300 rounded-md"
            required
          />
        </div>

        <!-- Judul -->
        <div class="mb-4">
          <label for="judul" class="block text-sm font-semibold text-gray-700">Judul Berita</label>
          <input
            id="judul"
            v-model="form.judul"
            type="text"
            class="mt-1 block w-full p-2 border border-gray-300 rounded-md"
            required
          />
        </div>

        <!-- Isi Berita -->
        <div class="mb-4">
          <label for="isi_berita" class="block text-sm font-semibold text-gray-700">Isi Berita</label>
          <MyEditor v-model="form.isi_berita" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" />
        </div>

        <!-- Kategori -->
        <div class="mb-4">
          <label class="block text-sm font-semibold text-gray-700">Kategori</label>
          <div class="flex flex-wrap gap-2">
            <label v-for="category in categories" :key="category" class="inline-flex items-center space-x-2">
              <input
                type="checkbox"
                :value="category"
                v-model="form.kategori"
                class="h-4 w-4 text-blue-600 border-gray-300 rounded"
              />
              <span class="text-sm text-gray-600">{{ category }}</span>
            </label>
          </div>
        </div>

        <!-- Gambar Utama -->
        <div class="mb-4">
          <label for="gambar_utama" class="block text-sm font-semibold text-gray-700">Gambar Utama</label>
          <!-- Current main image preview -->
          <div v-if="news.gambar_utama" class="mt-2 mb-2">
            <p class="text-sm text-gray-600">Current image:</p>
            <img
              :src="`/storage/${news.gambar_utama}`"
              alt="Current Main Image"
              class="w-32 h-32 object-cover rounded-md"
            />
          </div>
          <!-- New main image upload -->
          <input
            id="gambar_utama"
            type="file"
            accept="image/*"
            @change="event => form.gambar_utama = event.target.files[0]"
            class="mt-1 block w-full text-sm text-gray-500 border border-gray-300 rounded-md"
          />
          <!-- New image preview -->
          <div v-if="form.gambar_utama" class="mt-2">
            <p class="text-sm text-gray-600">New image:</p>
            <img
              :src="previewImage(form.gambar_utama)"
              alt="New Main Image Preview"
              class="w-32 h-32 object-cover rounded-md"
            />
          </div>
          <!-- Main image caption -->
          <label for="gambar_utama_keterangan" class="block text-sm font-semibold text-gray-700 mt-2">
            Keterangan Gambar Utama
          </label>
          <input
            id="gambar_utama_keterangan"
            type="text"
            v-model="form.gambar_utama_keterangan"
            class="mt-1 block w-full text-sm text-gray-500 border border-gray-300 rounded-md"
          />
        </div>

        <!-- Existing Attachments -->
        <div v-if="form.kept_attachments.length" class="mb-4">
          <label class="block text-sm font-semibold text-gray-700">Existing Attachments</label>
          <div v-for="(path, index) in form.kept_attachments" :key="index" class="flex items-center mb-2">
            <img
              :src="`/storage/${path}`"
              alt="Attachment Preview"
              class="w-16 h-16 object-cover rounded-md"
            />
            <input
              v-model="form.kept_attachments_keterangan[index]"
              type="text"
              placeholder="Keterangan gambar"
              class="ml-2 flex-grow p-2 border border-gray-300 rounded-md"
            />
            <button
              type="button"
              @click="removeExistingImage(index)"
              class="ml-2 text-red-600 hover:text-red-800"
            >
              Remove
            </button>
          </div>
        </div>

        <!-- New Attachments -->
        <div class="mb-4">
          <label for="gambar_lampiran" class="block text-sm font-semibold text-gray-700">Add New Attachments</label>
          <div v-for="(gambar, index) in form.gambar_lampiran" :key="index" class="flex items-center mb-2">
            <img
              :src="previewImage(gambar)"
              alt="New Attachment Preview"
              class="w-16 h-16 object-cover rounded-md"
            />
            <input
              v-model="form.gambar_lampiran_keterangan[index]"
              type="text"
              placeholder="Keterangan gambar"
              class="ml-2 flex-grow p-2 border border-gray-300 rounded-md"
            />
            <button
              type="button"
              @click="removeNewImage(index)"
              class="ml-2 text-red-600 hover:text-red-800"
            >
              Remove
            </button>
          </div>
          <input
            id="gambar_lampiran"
            type="file"
            accept="image/*"
            @change="addImage"
            class="mt-1 block w-full text-sm text-gray-500 border border-gray-300 rounded-md"
          />
          <p class="text-sm text-gray-500 mt-1">You can add up to 5 attachment images</p>
        </div>

        <!-- Submit Button -->
        <div class="text-center mt-6">
          <button
            type="submit"
            class="bg-blue-600 text-white px-6 py-2 rounded-md shadow hover:bg-blue-700 focus:outline-none"
            :disabled="form.processing"
          >
            {{ form.processing ? 'Updating...' : 'Update News' }}
          </button>
        </div>
      </form>
    </main>
  </div>
</template>