import js from '@eslint/js'
import pluginVue from 'eslint-plugin-vue'

export default [
  js.configs.recommended,
  ...pluginVue.configs['flat/essential'],
  {
    files: ['**/*.vue'],
    languageOptions: {
      parserOptions: {
        ecmaVersion: 'latest'
      }
    },
    rules: {
      // Vue 3 支持带参数的 v-model 语法，如 v-model:visible
      'vue/no-v-model-argument': 'off',
      // 允许使用 v-model 带参数的方式
      'vue/valid-v-model': 'off'
    }
  }
]
