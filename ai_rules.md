rules:
  for proper organisation endeavor to have respective files in respective folders.
  forexample users files should be placed inside user folder and not directly under src directory. 
  frontend files like welcome.vue should be placed inside frontend folder and not directly under src directory.
  same format for all directories. like controllers and many as we shall see
  laravel:
    php: ">=8.2"
    framework: "^12.0"
    starter_kit: "vue-inertia-ts-shadcn"
  broadcast:
    provider: "reverb"
    hooks from : import { useEcho } from "@laravel/echo-vue";
    client_hooks: ["useEcho", "useEchoModel"]
  inertia:
    version: inertiajs 2
    form_helper: "useForm"
    all redirections are handled by inertia.js
  ui:
    library: "shadcn-vue"
    All components already installed in the project
  versioning:
    composer_update: "composer global update laravel/installer"
    npm_outdated_check: true
