async function performSearch(query, lat = null, lng = null) {
  const resultsDiv = document.getElementById("results");
  resultsDiv.innerHTML = "Searching...";

  // Load current favorites to prevent duplicates
  const favoriteNames = new Set();
  try {
    const favRes = await fetch("api/favorites.php");
    const favData = await favRes.json();
    favData.forEach(fav => favoriteNames.add(fav.name));
  } catch (err) {
    console.warn("⚠️ Could not load favorites:", err);
  }

  try {
    let url = "api/search.php?q=" + encodeURIComponent(query);
    if (lat !== null && lng !== null) {
        url += `&lat=${lat}&lng=${lng}`;
    }

    const res = await fetch(url);
    const data = await res.json();

    if (!Array.isArray(data) || data.length === 0) {
      resultsDiv.innerHTML = "<p>No results found.</p>";
      return;
    }

    resultsDiv.innerHTML = "";

    for (const item of data) {
      const div = document.createElement("div");
      div.className = "restaurant";

      let weatherHTML = "";

      if (item.location?.lat && item.location?.lng) {
        try {
          const weatherRes = await fetch(`api/weather.php?lat=${item.location.lat}&lng=${item.location.lng}`);
          const weather = await weatherRes.json();

          weatherHTML = `
            <p><strong>Weather:</strong> ${weather.condition} | ${weather.temperature}°C | Wind: ${weather.wind_speed} km/h</p>
          `;
        } catch (e) {
          weatherHTML = "<p><em>Weather unavailable</em></p>";
        }
      }

      const websiteHTML = item.website ? `<p><strong>Website:</strong> <a href="${item.website}" target="_blank" style="word-break: break-all;">Visit Website</a></p>` : `<p><strong>Website:</strong> N/A</p>`;

      // Build base HTML
      div.innerHTML = `
        <h3>${item.name || item.title}</h3>
        <p><strong>Address:</strong> ${item.address}</p>
        <p><strong>Phone:</strong> ${item.phone ?? "N/A"}</p>
        <p><strong>Rating:</strong> ${item.rating ?? "N/A"} (${item.reviews} reviews)</p>
        ${websiteHTML}
        <p><strong>Map:</strong> <a href="${item.url}" target="_blank">View on Google Maps</a></p>
        ${weatherHTML}
      `;

      // Create "Add to Favorites" button
      const favButton = document.createElement("button");
      favButton.className = "fav-btn";

      const name = item.name || item.title || "Unnamed Restaurant";

      if (favoriteNames.has(name)) {
        favButton.textContent = "Already Favorited";
        favButton.disabled = true;
      } else {
        favButton.textContent = "Add to Favorites";
        favButton.addEventListener("click", async () => {
          const favoriteItem = {
            name: item.name || item.title || "Unnamed Restaurant",
            address: item.address ?? '',
            phone: item.phone ?? '',
            rating: item.rating ?? '',
            reviews: item.reviews ?? '',
            website: item.website ?? '',
            url: item.url ?? '',
            location: item.location ?? null
          };
        
          console.log("🟢 Sending to favorites:", favoriteItem); // debug log
        
          try {
            const res = await fetch("api/favorites.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify(favoriteItem),
            });
        
            const response = await res.json();
            console.log("🟢 Response from favorites.php:", response);
            alert(response.message || "Added to favorites");
        
            // Update UI after adding
            favButton.textContent = "Already Favorited";
            favButton.disabled = true;
            favoriteNames.add(favoriteItem.name);
          } catch (err) {
            alert("Error adding to favorites.");
            console.error(err);
          }
        });        
      }

      div.appendChild(favButton);

      const hr = document.createElement("hr");
      div.appendChild(hr);
      
      resultsDiv.appendChild(div);      
    }
  } catch (err) {
    resultsDiv.innerHTML = "<p>Error fetching data.</p>";
    console.error(err);
  }
}

document.getElementById("searchForm").addEventListener("submit", function (e) {
  e.preventDefault();
  const query = document.getElementById("query").value.trim();
  
  if (!query) {
    const resultsDiv = document.getElementById("results");
    resultsDiv.innerHTML = "<p>Please enter a search term.</p>";
    return;
  }

  performSearch(query);
});

document.getElementById("nearMeBtn").addEventListener("click", function () {
  const query = document.getElementById("query").value.trim();
  const resultsDiv = document.getElementById("results");

  if (!navigator.geolocation) {
    resultsDiv.innerHTML = "<p>Geolocation is not supported by your browser.</p>";
    return;
  }

  resultsDiv.innerHTML = "Asking for location permission...";
  
  navigator.geolocation.getCurrentPosition(
    (position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;
      performSearch(query, lat, lng);
    },
    (error) => {
      console.error("Geolocation error:", error);
      resultsDiv.innerHTML = "<p>Could not get your location. Please check permissions.</p>";
    }
  );
});