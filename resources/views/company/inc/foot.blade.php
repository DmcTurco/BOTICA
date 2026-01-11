    <!--   Core JS Files   -->
    <script src="{{ asset('assets/template/js/core/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/template/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/template/js/core/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/template/js/plugins/perfect-scrollbar.jquery.min.js') }}"></script>
    <!--  Google Maps Plugin    -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_KEY_HERE"></script>
    <!-- Chart JS -->
    <script src="{{ asset('assets/template/js/plugins/chartjs.min.js') }}"></script>
    <!--  Notifications Plugin    -->
    <script src="{{ asset('assets/template/js/plugins/bootstrap-notify.js') }}"></script>
    <!-- Control Center for Now Ui Dashboard: parallax effects, scripts for the example pages etc -->
    <script src="{{ asset('assets/template/js/paper-dashboard.min.js?v=2.0.0') }}" type="text/javascript"></script>
    <!-- Paper Dashboard DEMO methods, don't include it in your project! -->
    <script src="{{ asset('assets/template/demo/demo.js') }}"></script>
    <!-- Sharrre libray -->
    <script src="{{ asset('assets/template/demo/jquery.sharrre.js')}}"></script>
    
    @stack('scripts')
