import { test, expect } from '@playwright/test'

test.describe('SmashConnect - Parcours utilisateur', () => {
  test('affiche la page d\'accueil avec le titre', async ({ page }) => {
    await page.goto('/')
    await expect(page.locator('h1.hero-title')).toHaveText('Bienvenue sur SmashConnect')
  })

  test('affiche les liens de navigation', async ({ page }) => {
    await page.goto('/')
    await expect(page.locator('.navlink').first()).toHaveText('Acceuil')
    await expect(page.locator('.navlink').nth(1)).toHaveText('Forum')
    await expect(page.locator('.navlink').nth(2)).toHaveText('Joueurs')
  })

  test('affiche les boutons de connexion et inscription pour un visiteur', async ({ page }) => {
    await page.goto('/')
    await expect(page.locator('.navbar-right')).toContainText('Se connecter')
    await expect(page.locator('.navbar-right')).toContainText('Créer un compte')
  })

  test('navigation vers la page de connexion', async ({ page }) => {
    await page.goto('/')
    await page.locator('.navbar-right a').first().click()
    await expect(page).toHaveURL('/login')
    await expect(page.locator('#pseudo')).toBeVisible()
  })

  test('navigation vers la page d\'inscription', async ({ page }) => {
    await page.goto('/')
    await page.locator('.navbar-right a').last().click()
    await expect(page).toHaveURL('/register')
    await expect(page.locator('#pseudo')).toBeVisible()
  })

  test('navigation vers le forum', async ({ page }) => {
    await page.goto('/')
    await page.locator('.navlink').nth(1).click()
    await expect(page).toHaveURL('/forum')
    await expect(page.locator('h1.title')).toHaveText('Forum')
  })

  test('navigation vers les joueurs', async ({ page }) => {
    await page.goto('/')
    await page.locator('.navlink').nth(2).click()
    await expect(page).toHaveURL('/players')
    await expect(page.locator('h1.title')).toHaveText('Joueurs')
  })

  test('redirige vers login pour la page profil non authentifié', async ({ page }) => {
    await page.goto('/profile')
    await expect(page).toHaveURL(/\/login/)
  })

  test('la page d\'accueil a des boutons d\'action', async ({ page }) => {
    await page.goto('/')
    const heroActions = page.locator('.hero-actions')
    await expect(heroActions).toContainText('Créer un compte')
    await expect(heroActions).toContainText('Accéder au forum')
  })

  test('le menu mobile s\'ouvre et se ferme', async ({ page }) => {
    await page.goto('/')
    await page.locator('.burger').click()
    await expect(page.locator('#mobile-menu')).toBeVisible()
    await page.locator('.burger').click()
    await expect(page.locator('#mobile-menu')).not.toBeVisible()
  })

  test('connexion avec identifiants invalides affiche une erreur', async ({ page }) => {
    await page.goto('/login')
    await page.fill('#pseudo', 'invalide')
    await page.fill('#password', 'mauvais')
    await page.locator('button[type="submit"]').click()
    await expect(page.locator('.error')).toBeVisible({ timeout: 10000 })
  })
})
