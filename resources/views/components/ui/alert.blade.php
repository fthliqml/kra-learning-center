@if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{{ session('success') }}",
                showConfirmButton: false,
                timer: 2000
            });
        });
    </script>
@endif

@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                html: `
                <div style="text-align:left;">
                    <ul style="list-style-type:none; padding:0; margin:0;">
                        @foreach ($errors->all() as $error)
                            <li style="margin:4px 0; padding:6px 10px; background:#ffe5e5; border-left:4px solid #e3342f; border-radius:4px; font-size:14px;">
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            `,
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#e3342f'
            });
        });
    </script>
@endif
