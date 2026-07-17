import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import CreateTopicModal from '@/components/CreateTopicModal.vue'
import api from '@/services/api'

vi.mock('@/services/api', () => ({
  default: {
    post: vi.fn(),
    defaults: { headers: { common: {} } },
  },
}))

const categories = [
  { id: 1, name: 'General' },
  { id: 2, name: 'Technique' },
]

describe('CreateTopicModal', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('does not render when not visible', () => {
    const wrapper = mount(CreateTopicModal, {
      props: { visible: false, categories },
    })
    expect(wrapper.find('.modal-panel').exists()).toBe(false)
  })

  it('renders form fields when visible', () => {
    const wrapper = mount(CreateTopicModal, {
      props: { visible: true, categories },
    })
    expect(wrapper.find('.modal-panel').exists()).toBe(true)
    expect(wrapper.find('select').exists()).toBe(true)
    expect(wrapper.find('input').exists()).toBe(true)
    expect(wrapper.find('textarea').exists()).toBe(true)
  })

  it('displays category options', () => {
    const wrapper = mount(CreateTopicModal, {
      props: { visible: true, categories },
    })
    const options = wrapper.findAll('option')
    expect(options).toHaveLength(3)
    expect(options.at(1).text()).toBe('General')
    expect(options.at(2).text()).toBe('Technique')
  })

  it('does not submit if fields are empty', async () => {
    const wrapper = mount(CreateTopicModal, {
      props: { visible: true, categories },
    })
    await wrapper.find('form').trigger('submit.prevent')
    expect(api.post).not.toHaveBeenCalled()
  })

  it('creates topic on submit and emits created', async () => {
    const newTopic = { id: 1, title: 'My Topic', category: { id: 1, name: 'General' } }
    api.post.mockResolvedValueOnce({ data: newTopic })

    const wrapper = mount(CreateTopicModal, {
      props: { visible: true, categories },
    })
    wrapper.vm.categoryId = 1
    wrapper.vm.title = 'My Topic'
    wrapper.vm.content = 'Topic content here'
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(api.post).toHaveBeenCalledWith('/forums', {
      category_id: 1,
      title: 'My Topic',
      content: 'Topic content here',
    })
    expect(wrapper.emitted('created')).toBeTruthy()
    expect(wrapper.emitted('created')[0]).toEqual([newTopic])
  })

  it('resets fields on submit', async () => {
    api.post.mockResolvedValueOnce({ data: {} })

    const wrapper = mount(CreateTopicModal, {
      props: { visible: true, categories },
    })
    wrapper.vm.categoryId = 1
    wrapper.vm.title = 'My Topic'
    wrapper.vm.content = 'Content'
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.vm.categoryId).toBe('')
    expect(wrapper.vm.title).toBe('')
    expect(wrapper.vm.content).toBe('')
  })

  it('shows error on failure', async () => {
    api.post.mockRejectedValueOnce({
      response: { data: { message: 'Erreur création' } },
    })

    const wrapper = mount(CreateTopicModal, {
      props: { visible: true, categories },
    })
    wrapper.vm.categoryId = 1
    wrapper.vm.title = 'My Topic'
    wrapper.vm.content = 'Content'
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.text()).toContain('Erreur création')
  })

  it('shows sending state while submitting', async () => {
    api.post.mockImplementationOnce(() => new Promise(() => {}))

    const wrapper = mount(CreateTopicModal, {
      props: { visible: true, categories },
    })
    wrapper.vm.categoryId = 1
    wrapper.vm.title = 'My Topic'
    wrapper.vm.content = 'Content'
    await wrapper.find('form').trigger('submit.prevent')
    await wrapper.vm.$nextTick()

    expect(wrapper.find('.btn-primary').text()).toBe('Création…')
    expect(wrapper.find('.btn-primary').element.disabled).toBe(true)
  })

  it('resets fields when visible becomes true', async () => {
    const wrapper = mount(CreateTopicModal, {
      props: { visible: false, categories },
    })
    wrapper.vm.categoryId = 1
    wrapper.vm.title = 'Old'
    wrapper.vm.content = 'Old Content'
    wrapper.vm.error = 'some error'

    await wrapper.setProps({ visible: true })

    expect(wrapper.vm.categoryId).toBe('')
    expect(wrapper.vm.title).toBe('')
    expect(wrapper.vm.content).toBe('')
    expect(wrapper.vm.error).toBeNull()
  })

  it('emits close on cancel button', async () => {
    const wrapper = mount(CreateTopicModal, {
      props: { visible: true, categories },
    })
    await wrapper.find('.btn:not(.btn-primary)').trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('emits close on backdrop click', async () => {
    const wrapper = mount(CreateTopicModal, {
      props: { visible: true, categories },
    })
    await wrapper.find('.modal-backdrop').trigger('click.self')
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
