<template>
  <ckeditor
    name="editor1"
    ref="editor"
    tag-name="textarea"
    @ready="onEditorReady"
    @input="update"
    :value="value"
  ></ckeditor>
</template>

<script>
import CKEditor from "ckeditor4-vue";
export default {
  props: {
    // Value of editor
    value: {
      type: String,
      default: "",
    },
    // Height of editor passed with :height="amount";
    height: {
      type: String,
      default: "400",
    },
  },
  components: {
    ckeditor: CKEditor.component,
  },

  methods: {
    update(val) {
      this.$emit("input", val);
    },

    onEditorReady() {
      for (var instanceName in CKEDITOR.instances) {
        if (Object.entries(CKEDITOR.instances[instanceName]).length) {
          // Sets width = 100% and height of editor.
          CKEDITOR.instances[instanceName].resize("100%", this.height);
        }
      }
    },
  },

  data() {
    return {};
  },
};
</script>
