async function loadFavorites() {
    const container = document.getElementById("favoritesList");
    container.innerHTML = "Loading...";
  
    try {
      const res = await fetch("api/favorites.php");
      const data = await res.json();
  
      if (!Array.isArray(data) || data.length === 0) {
        container.innerHTML = "<p>No favorites yet.</p>";
        return;
      }
  
      container.innerHTML = "";
  
      data.forEach(item => {
        const div = document.createElement("div");
        div.className = "restaurant";
  
        div.innerHTML = `
          <h3>${item.name}</h3>
          <p><strong>Address:</strong> ${item.address}</p>
          <p><strong>Phone:</strong> ${item.phone ?? "N/A"}</p>
          <p><strong>Rating:</strong> ${item.rating ?? "N/A"} (${item.reviews} reviews)</p>
          <p><strong>Map:</strong> <a href="${item.url}" target="_blank">View on Google Maps</a></p>
          <button class="remove-btn">Remove</button>
          <hr>
        `;
  
        // Add remove logic
        div.querySelector(".remove-btn").addEventListener("click", async () => {
          const confirmRemove = confirm(`Remove "${item.name}" from favorites?`);
          if (!confirmRemove) return;
  
          try {
            const res = await fetch("api/favorites.php", {
              method: "DELETE",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ name: item.name }),
            });
            const result = await res.json();
            alert(result.message || "Removed");
            loadFavorites(); // Reload list
          } catch (err) {
            alert("Failed to remove favorite.");
            console.error(err);
          }
        });
  
        container.appendChild(div);
      });
    } catch (err) {
      container.innerHTML = "<p>Error loading favorites.</p>";
      console.error(err);
    }
  }
  
  // Run on load
  loadFavorites();  