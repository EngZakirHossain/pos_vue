<template>
  <div class="wrapper">
    <navbar />
    <sidebar />
    <!-- Main content -->
    <section class="content-wrapper">
      <div class="container-fluid page-padding">
        <child />
      </div>
      <!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <footer class="main-footer">
      <strong v-if="appInfo">{{ appInfo.copyright }}.</strong>
      Developed by
      <a href="https://mrzakir.com/" target="__blank">Zakir Hossain</a>
    </footer>

    <!-- Control Sidebar -->
    <sidebar-controll />
    <!-- /.control-sidebar -->
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import Navbar from '~/components/Navbar'
import Sidebar from '~/components/Sidebar'
import SidebarControll from '~/components/SidebarControll'
export default {
  name: 'MainLayout',
  data: () => ({
    year: new Date().getFullYear(),
  }),
  components: {
    Navbar,
    Sidebar,
    SidebarControll,
  },
  // Map Getters
  computed: {
    ...mapGetters('operations', ['appInfo']),
  },
  methods: {
    addBodyClass(className) {
      document.body.classList.toggle(className)
    },
  },
}
</script>
