<script setup>
import { reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import BreezeValidationErrors from '@/Components/ValidationErrors.vue';

defineProps({
    errors:Object
});

const form = reactive({
    title: null,
    content:null
});

const submitFunction = () => {
    //第一引数のurlにPOSTメソッドによって第二引数のデータを持って送信。
    //vueファイル内において、laravelのroutes/web.phpにおいて定義したルーティング、
    //この場合postメソッドで/inertiaというパスにformというデータを送信する
    //というルーティング。を実行したということになる。
    //routeメソッドの代わり。
    Inertia.post('/inertia', form);
}



</script>

<template>
    <BreezeValidationErrors :errors="errors" />
    <form @submit.prevent="submitFunction">
        <input type="text" name="title" v-model="form.title"><br>
        <div v-if="errors.title">{{ errors.title }}</div>
        <input type="text" name="content" v-model="form.content"><br>
        <div v-if="errors.content">{{ errors.content }}</div>
        <button>送信</button>
    </form>
</template>
