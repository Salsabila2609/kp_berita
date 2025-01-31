<script setup>
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import MyEditor from '@/Components/MyEditor.vue'; // Pastikan MyEditor di-import

// Form setup with default values
const form = useForm({
  penulis: 'Pemkab Madiun', // default penulis
  judul: '',
  isi_berita: '',
  gambar_utama: null,
  gambar_utama_keterangan: '', // Keterangan gambar utama
  gambar_lampiran: [],  // Array for multiple attachment images
  gambar_lampiran_keterangan: [], // Keterangan untuk setiap gambar lampiran
  kategori: [],
});

// Categories for checkbox options
const categories = [
  'Pemerintah',
  'Berita Daerah', 
  'Umum',
  'Ekonomi',
  'Seni Budaya dan Hiburan',
  'Lowongan',
];

// Submit function
const submit = () => {
  // Convert the categories array to a JSON string before submission
  form.kategori = JSON.stringify(form.kategori);

  // Create a FormData object
  const formData = new FormData();

  // Append form data to FormData object
  formData.append('penulis', form.penulis);
  formData.append('judul', form.judul);
  formData.append('isi_berita', form.isi_berita);
  formData.append('kategori', form.kategori);
  formData.append('gambar_utama_keterangan', form.gambar_utama_keterangan); // Menambahkan keterangan gambar utama

  // If gambar utama is selected, append it to FormData object
  if (form.gambar_utama) {
    formData.append('gambar_utama', form.gambar_utama);
  }

  // Append all images in gambar_lampiran array and their corresponding keterangan
  form.gambar_lampiran.forEach((file, index) => {
    formData.append(`gambar_lampiran[${index}]`, file); // Fixed string interpolation
    formData.append(`gambar_lampiran_keterangan[${index}]`, form.gambar_lampiran_keterangan[index]); // Fixed string interpolation
  });

  // Submit the form data to the Laravel backend
  form.post('/news', {
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data', // Important for file upload
    },
  });
};

// Method to add image to the form
const addImage = (event) => {
  if (event.target.files.length > 0) {
    form.gambar_lampiran.push(event.target.files[0]);
    form.gambar_lampiran_keterangan.push(''); // Menambahkan entri kosong untuk keterangan gambar
  }
};

// Method to remove an attachment image
const removeImage = (index) => {
  form.gambar_lampiran.splice(index, 1);
};

// Method to preview the uploaded image
const previewImage = (file) => {
  return URL.createObjectURL(file);
};
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-blue-600 text-white shadow">
      <div class="container mx-auto px-4 py-2 flex justify-between items-center">
        <Link href="/dashboard" class="text-xl font-bold hover:underline">
          Back to Dashboard
        </Link>
      </div>
    </nav>

    <main class="container mx-auto mt-10 p-6 bg-white shadow rounded">
      <h1 class="text-2xl font-bold text-center mb-6">Create News</h1>

      <!-- Form to create news -->
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

        <!-- Judul Berita -->
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
      <!-- Isi Berita -->
      <div class="mb-4">
        <label for="isi_berita" class="block text-sm font-semibold text-gray-700">Isi Berita</label>
        <!-- MyEditor untuk Isi Berita -->
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
          <input
            id="gambar_utama"
            type="file"
            accept="image/*"
            @change="event => form.gambar_utama = event.target.files[0]"
            class="mt-1 block w-full text-sm text-gray-500 border border-gray-300 rounded-md"
            required
          />
          <!-- Preview for Gambar Utama -->
          <div v-if="form.gambar_utama" class="mt-2">
            <img
              :src="previewImage(form.gambar_utama)"
              alt="Preview Image"
              class="w-32 h-32 object-cover rounded-md"
            />
          </div>

          <!-- Input Keterangan untuk Gambar Utama -->
          <label for="gambar_utama_keterangan" class="block text-sm font-semibold text-gray-700 mt-2">Keterangan Gambar Utama</label>
          <input
            id="gambar_utama_keterangan"
            type="text"
            v-model="form.gambar_utama_keterangan"
            placeholder="Masukkan keterangan gambar utama"
            class="mt-1 block w-full text-sm text-gray-500 border border-gray-300 rounded-md"
          />
        </div>

        <!-- Gambar Lampiran -->
        <div class="mb-4">
          <label for="gambar_lampiran" class="block text-sm font-semibold text-gray-700">Gambar Lampiran</label>
          <div v-for="(gambar, index) in form.gambar_lampiran" :key="index" class="flex items-center mb-2">
            <span class="mr-2">{{ gambar.name }}</span>
            <button
              type="button"
              @click="removeImage(index)"
              class="text-red-600 hover:text-red-800"
            >
              Remove
            </button>
            <!-- Preview for Lampiran -->
            <img
              v-if="gambar"
              :src="previewImage(gambar)"
              alt="Attachment Preview"
              class="w-16 h-16 object-cover rounded-md ml-2"
            />
            <!-- Input Keterangan untuk Gambar Lampiran -->
            <input
              v-model="form.gambar_lampiran_keterangan[index]"
              type="text"
              placeholder="Keterangan gambar lampiran"
              class="mt-1 block w-full text-sm text-gray-500 border border-gray-300 rounded-md"
            />
          </div>
          <input
            id="gambar_lampiran"
            type="file"
            accept="image/*"
            @change="addImage"
            class="mt-1 block w-full text-sm text-gray-500 border border-gray-300 rounded-md"
          />
        </div>

        <!-- Submit Button -->
        <div class="text-center">
          <button
            type="submit"
            class="bg-blue-600 text-white px-6 py-2 rounded-md shadow hover:bg-blue-700 focus:outline-none"
          >
            Create News
          </button>
        </div>
      </form>
    </main>
  </div>
</template>

