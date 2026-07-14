<style>
    footer.footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: auto;
        z-index: 1050;
        height: var(--admin-footer-height, 35px);
        padding: 0;
        margin: 0;
        align-items: center;
        background-color: var(--sidebar-bg, #192431);
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

    <div class="col-2 text-end version-info pe-5 me-5" >
        <b>Version</b> 2.4.0
    </div>
</footer>
