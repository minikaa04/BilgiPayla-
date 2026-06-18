</main>

<script>
const BASE_URL = '<?= BASE_URL ?>';

// Generic AJAX helper
function ajaxPost(url, data) {
    return fetch(BASE_URL + '/' + url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(data)
    }).then(r => r.json());
}
</script>
</body>
</html>
