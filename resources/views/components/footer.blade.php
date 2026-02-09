<style>
    footer.footer {
        position: fixed;
        bottom: 0;
        width: 100%;
        z-index: 1050;
        height: 5%;
        padding: 0;
        margin: 0;
        align-items: center;
        background-color: #343A40;
        display: flex;
        justify-content: space-between;

    }

    @media (max-width: 990px) {
        .version-info {
            display: none;
        }
        .copyright-info {
            width: 100%;
            text-align: center;
        }
        .reserved-info {
            display: none;
        }
    }
</style>

<footer class="footer row text-white g-0">
    <div class="col-2"></div>
    <div class="col-4 text-center copyright-info">
        <strong>Copyright &copy; 2026 &nbsp; <a href="https://aviatechnic.ca"  class="text-primary">Aviatechnik</a></strong>
    </div>
    <div class="col-3 text-center reserved-info" >
        <strong>All rights reserved.</strong>
    </div>
    <div class="col-1"></div>
    <div class="col-2 text-end version-info pe-4" >
        <b>Version</b> 1.4.0
    </div>
</footer>
