import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import PaginationControls from '@/components/Pagination.vue'

describe('PaginationControls', () => {
  it('renders page indicator', () => {
    const wrapper = mount(PaginationControls, {
      props: { page: 1, totalPages: 5 },
    })
    expect(wrapper.text()).toContain('Page 1 / 5')
  })

  it('disables previous button on first page', () => {
    const wrapper = mount(PaginationControls, {
      props: { page: 1, totalPages: 5 },
    })
    const prevBtn = wrapper.findAll('button').at(0)
    expect(prevBtn.attributes('disabled')).toBeDefined()
  })

  it('disables next button on last page', () => {
    const wrapper = mount(PaginationControls, {
      props: { page: 5, totalPages: 5 },
    })
    const nextBtn = wrapper.findAll('button').at(1)
    expect(nextBtn.attributes('disabled')).toBeDefined()
  })

  it('enables next button when not on last page', () => {
    const wrapper = mount(PaginationControls, {
      props: { page: 1, totalPages: 5 },
    })
    const nextBtn = wrapper.findAll('button').at(1)
    expect(nextBtn.attributes('disabled')).toBeUndefined()
  })

  it('emits update:page when clicking next', async () => {
    const wrapper = mount(PaginationControls, {
      props: { page: 1, totalPages: 5 },
    })
    const nextBtn = wrapper.findAll('button').at(1)
    await nextBtn.trigger('click')
    expect(wrapper.emitted('update:page')).toBeTruthy()
    expect(wrapper.emitted('update:page')[0]).toEqual([2])
  })

  it('emits update:page when clicking previous', async () => {
    const wrapper = mount(PaginationControls, {
      props: { page: 3, totalPages: 5 },
    })
    const prevBtn = wrapper.findAll('button').at(0)
    await prevBtn.trigger('click')
    expect(wrapper.emitted('update:page')[0]).toEqual([2])
  })

  it('clamps page to valid range', async () => {
    const wrapper = mount(PaginationControls, {
      props: { page: 1, totalPages: 5 },
    })
    const prevBtn = wrapper.findAll('button').at(0)
    await prevBtn.trigger('click')
    expect(wrapper.emitted('update:page')[0]).toEqual([1])
  })

  it('emits update:pageSize when select changes', async () => {
    const wrapper = mount(PaginationControls, {
      props: { page: 1, totalPages: 5, pageSize: 10 },
    })
    const select = wrapper.find('select')
    await select.setValue('20')
    expect(wrapper.emitted('update:pageSize')).toBeTruthy()
    expect(wrapper.emitted('update:pageSize')[0]).toEqual([20])
  })

  it('renders page size options', () => {
    const wrapper = mount(PaginationControls, {
      props: { page: 1, totalPages: 5, pageSizeOptions: [5, 10, 20, 50] },
    })
    const options = wrapper.findAll('option')
    expect(options).toHaveLength(4)
    expect(options.at(0).text()).toBe('5')
  })

  it('shows totalPages as 1 when 0', () => {
    const wrapper = mount(PaginationControls, {
      props: { page: 1, totalPages: 0 },
    })
    expect(wrapper.text()).toContain('Page 1 / 1')
  })
})
