<?php
$pageTitle = 'Varita Mágica ✨';
ob_start();
?>

<div class="card" style="text-align: center; padding: 40px; min-height: 400px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
    <h1 style="color: #1B3C84; margin-bottom: 20px;">Varita Mágica ✨</h1>
    <p style="color: #666; font-size: 1.2em;">¡Haz clic en el objeto para transformarlo!</p>
    
    <div id="magic-container" onclick="transformar()" style="cursor: pointer; font-size: 120px; margin: 40px; transition: transform 0.3s ease, opacity 0.3s ease; user-select: none;">
        🦆
    </div>
    
    <div style="margin-top: 20px;">
        <p>Secuencia: Pato 🦆 → Sapo 🐸 → Globo 🎈</p>
    </div>
</div>

<script>
    const objetos = ['🦆', '🐸', '🎈'];
    let indice = 0;
    const container = document.getElementById('magic-container');

    function transformar() {
        // Animación de desaparición
        container.style.transform = 'scale(0.1) rotate(180deg)';
        container.style.opacity = '0';
        
        setTimeout(() => {
            // Cambiar objeto
            indice = (indice + 1) % objetos.length;
            container.innerHTML = objetos[indice];
            
            // Animación de aparición
            container.style.transform = 'scale(1.2) rotate(0deg)';
            container.style.opacity = '1';
            
            // Efecto de rebote
            setTimeout(() => {
                container.style.transform = 'scale(1)';
            }, 200);
            
            // Sonido opcional (comentado para no molestar si no se requiere)
            // playSound(); 
        }, 300);
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
